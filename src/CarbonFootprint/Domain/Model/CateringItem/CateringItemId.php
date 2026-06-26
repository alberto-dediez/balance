<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CateringItem;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class CateringItemId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
