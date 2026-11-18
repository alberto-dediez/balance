<?php

declare(strict_types=1);

namespace App\Shared\Application\Period;

final class DailyPeriod
{
    public const string WEEK = 'week';
    public const string MONTH = 'month';
    public const string ALL = 'all';

    private function __construct(
        public string $type,
        public ?\DateTimeImmutable $from,
        public ?\DateTimeImmutable $to,
    ) {}

    public static function week(\DateTimeImmutable $date): self
    {
        $from = $date->setTime(0, 0)->modify('monday this week');

        return new self(self::WEEK, $from, $from->modify('+7 days'));
    }

    public static function month(\DateTimeImmutable $date): self
    {
        $from = $date->setTime(0, 0)->modify('first day of this month');

        return new self(self::MONTH, $from, $from->modify('+1 month'));
    }

    public static function all(): self
    {
        return new self(self::ALL, null, null);
    }

    public static function of(string $type, \DateTimeImmutable $date): self
    {
        return match ($type) {
            self::MONTH => self::month($date),
            self::ALL => self::all(),
            default => self::week($date),
        };
    }

    public function isAll(): bool
    {
        return self::ALL === $this->type;
    }

    public function previous(): self
    {
        return $this->shift('-');
    }

    public function next(): self
    {
        return $this->shift('+');
    }

    public function containsToday(\DateTimeImmutable $now): bool
    {
        if (null === $this->from || null === $this->to) {
            return true;
        }

        return $this->from <= $now && $now < $this->to;
    }

    public function lastDay(): ?\DateTimeImmutable
    {
        return $this->to?->modify('-1 day');
    }

    private function shift(string $sign): self
    {
        if (null === $this->from) {
            return $this;
        }

        return match ($this->type) {
            self::MONTH => self::month($this->from->modify($sign . '1 month')),
            default => self::week($this->from->modify($sign . '7 days')),
        };
    }
}
