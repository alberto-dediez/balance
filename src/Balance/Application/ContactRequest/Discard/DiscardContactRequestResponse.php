<?php

declare(strict_types=1);

namespace App\Balance\Application\ContactRequest\Discard;

use App\Balance\Domain\Model\ContactRequest\ContactRequestId;
use App\Shared\Application\CommandBus\CommandResponse;

class DiscardContactRequestResponse implements CommandResponse
{
    public function __construct(
        public ContactRequestId $contactRequestId,
    ) {}
}
