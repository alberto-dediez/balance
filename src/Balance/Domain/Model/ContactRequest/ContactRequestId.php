<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContactRequest;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class ContactRequestId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
