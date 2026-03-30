<?php

declare(strict_types=1);

namespace App\Security\Application\Account\Remove;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RemoveAccountRequest implements Command
{
    public function __construct(public AccountId $accountId) {}
}
