<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ElectricityMix;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class ElectricityMixEmissionFactorId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
