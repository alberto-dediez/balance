<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class PropertyCarbonFootprintActivityId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
