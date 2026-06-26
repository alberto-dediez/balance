<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CateringItem;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class CateringItemEmissionFactorId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
