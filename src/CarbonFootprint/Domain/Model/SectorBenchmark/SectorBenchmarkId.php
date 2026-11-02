<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\SectorBenchmark;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class SectorBenchmarkId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
