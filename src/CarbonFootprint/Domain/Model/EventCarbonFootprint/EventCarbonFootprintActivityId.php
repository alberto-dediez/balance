<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class EventCarbonFootprintActivityId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
