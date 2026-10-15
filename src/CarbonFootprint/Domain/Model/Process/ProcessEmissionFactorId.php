<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Process;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class ProcessEmissionFactorId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
