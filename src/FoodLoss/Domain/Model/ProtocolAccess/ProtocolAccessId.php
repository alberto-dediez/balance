<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\ProtocolAccess;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class ProtocolAccessId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
