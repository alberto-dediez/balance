<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EconomicSector;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class EconomicSectorEmissionFactorId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
