<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payee;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class PayeeId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
