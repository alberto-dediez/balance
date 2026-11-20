<?php

declare(strict_types=1);

namespace App\Balance\Application\ContactRequest\MarkAsManaged;

use App\Balance\Domain\Model\ContactRequest\ContactRequestId;
use App\Shared\Application\CommandBus\Command;

class MarkContactRequestAsManagedRequest implements Command
{
    public function __construct(
        public ContactRequestId $contactRequestId,
        public ?\DateTimeImmutable $now = null,
    ) {}
}
