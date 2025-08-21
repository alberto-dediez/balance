<?php

namespace App\Security\Application\Account\Update;

use App\Shared\Application\CommandBus\Command;
use App\Security\Domain\Model\Account\AccountId;

class UpdateAccountRequest implements Command
{
    public function __construct(public AccountId $accountId, public string $name, public bool $active)
    {

    }
}