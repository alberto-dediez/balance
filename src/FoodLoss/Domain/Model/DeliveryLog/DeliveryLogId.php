<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DeliveryLog;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class DeliveryLogId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
