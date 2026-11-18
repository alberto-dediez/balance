<?php

declare(strict_types=1);

namespace App\Shared\Application\Assistant;

final readonly class ToolContext
{
    private const string MIDNIGHT = '00:00:00.000000';

    public function __construct(
        public \DateTimeImmutable $now,
        public \DateTimeZone $timezone,
        public int $documentMaxBytes = 10485760,
    ) {}

    public function date(?\DateTimeInterface $date): ?string
    {
        if (null === $date || self::MIDNIGHT === $date->format('H:i:s.u')) {
            return $date?->format(ToolArguments::DATE_FORMAT);
        }

        return \DateTimeImmutable::createFromInterface($date)->setTimezone($this->timezone)->format(ToolArguments::DATE_FORMAT);
    }

    public function dateTime(?\DateTimeInterface $date): ?string
    {
        return null === $date ? null : \DateTimeImmutable::createFromInterface($date)->setTimezone($this->timezone)->format(ToolArguments::DATE_TIME_FORMAT);
    }
}
