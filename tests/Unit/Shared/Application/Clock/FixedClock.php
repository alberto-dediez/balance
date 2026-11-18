<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Clock;

use App\Shared\Application\Clock\Clock;

class FixedClock implements Clock
{
    public function __construct(private readonly \DateTimeImmutable $now = new \DateTimeImmutable()) {}

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}
