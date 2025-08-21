<?php

namespace App\Security\Application\Account\Update;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateAccountResponse implements CommandResponse
{
    public function __construct(public AccountId $accountId, public string $name, public bool $active)
    {
        
    }
}