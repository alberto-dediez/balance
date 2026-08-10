<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payment;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class PaymentId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
