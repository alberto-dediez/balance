<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\SectorBenchmark\Refresh;

use App\Shared\Application\CommandBus\TimedCommand;

class RefreshSectorBenchmarksRequest implements TimedCommand
{
    public function __construct(
        public \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}

    public function withNow(\DateTimeImmutable $now): self
    {
        return new self($now);
    }
}
