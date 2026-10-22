<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class CompanyCarbonFootprintNonConsolidatedPropertyId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
