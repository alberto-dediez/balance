<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Event;

class DateRange implements \Stringable
{
    public function __construct(
        private readonly ?\DateTimeImmutable $startDate = null,
        private readonly ?\DateTimeImmutable $endDate = null,
    ) {
        if (null !== $this->startDate && null !== $this->endDate && $this->startDate > $this->endDate) {
            throw new InvalidDateRangeException($this->startDate, $this->endDate);
        }
    }

    public function startDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function endDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function __toString(): string
    {
        return implode(' - ', array_filter([
            $this->startDate?->format('d/m/Y'),
            $this->endDate?->format('d/m/Y'),
        ]));
    }
}
