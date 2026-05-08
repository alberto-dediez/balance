<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class CriticalPointSurplusCauseLinkId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
