<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ElectricityMarketer;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class ElectricityMarketerEmissionFactorId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
