<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Protocol;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class ProtocolId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
