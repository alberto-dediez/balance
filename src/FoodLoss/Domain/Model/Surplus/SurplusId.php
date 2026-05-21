<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class SurplusId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
