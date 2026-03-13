<?php

namespace App\FoodLoss\Domain\Model\Action;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class ActionId implements AggregateRootId
{
    use AggregateRootIdTrait;
}