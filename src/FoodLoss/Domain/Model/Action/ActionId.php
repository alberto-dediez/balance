<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Action;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class ActionId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
