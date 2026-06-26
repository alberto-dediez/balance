<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MachineryFuel;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class MachineryFuelEmissionFactorId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
