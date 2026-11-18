<?php

declare(strict_types=1);

namespace App\Shared\Application\Assistant;

final readonly class ToolArguments
{
    public const string DATE_FORMAT = 'Y-m-d';
    public const string DATE_TIME_FORMAT = 'Y-m-d H:i';
    public const int DEFAULT_LIMIT = 20;

    /** @param array<string, list<string>> $values */
    public function __construct(
        private array $values,
        private \DateTimeZone $timezone,
    ) {}

    public function has(string $name): bool
    {
        return isset($this->values[$name]) && [] !== $this->values[$name];
    }

    public function string(string $name): ?string
    {
        return $this->values[$name][0] ?? null;
    }

    /** @return list<string> */
    public function strings(string $name): array
    {
        return $this->values[$name] ?? [];
    }

    public function int(string $name): ?int
    {
        $value = $this->string($name);

        return null === $value ? null : (int) $value;
    }

    public function limit(): int
    {
        return $this->int('limit') ?? self::DEFAULT_LIMIT;
    }

    public function decimal(string $name): ?string
    {
        return $this->string($name);
    }

    public function bool(string $name): ?bool
    {
        $value = $this->string($name);

        return null === $value ? null : '1' === $value;
    }

    public function date(string $name): ?\DateTimeImmutable
    {
        $value = $this->string($name);

        if (null === $value) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!' . self::DATE_FORMAT, $value);

        return false === $date ? null : $date;
    }

    public function dateTime(string $name): ?\DateTimeImmutable
    {
        $value = $this->string($name);

        if (null === $value) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!' . self::DATE_TIME_FORMAT, $value, $this->timezone);

        return false === $date ? null : $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
    }

    /** @return array<string, list<string>> */
    public function all(): array
    {
        return $this->values;
    }

    public function timezone(): \DateTimeZone
    {
        return $this->timezone;
    }
}
