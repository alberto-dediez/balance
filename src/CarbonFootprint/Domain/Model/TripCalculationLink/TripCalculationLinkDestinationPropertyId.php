<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculationLink;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class TripCalculationLinkDestinationPropertyId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
