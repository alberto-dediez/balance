<?php

namespace App\Security\Application\Account\Remove;

use App\Shared\Application\CommandBus\Command;
use App\Security\Domain\Model\Account\AccountId;

class RemoveAccountRequest implements Command
{
    public function __construct(public AccountId $accountId)
    {

    }
}