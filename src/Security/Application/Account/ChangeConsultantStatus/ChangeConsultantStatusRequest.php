<?php

declare(strict_types=1);

namespace App\Security\Application\Account\ChangeConsultantStatus;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class ChangeConsultantStatusRequest implements Command
{
    public function __construct(public AccountId $accountId, public bool $consultant = false) {}
}
