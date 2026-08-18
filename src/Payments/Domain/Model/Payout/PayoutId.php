<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class PayoutId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
