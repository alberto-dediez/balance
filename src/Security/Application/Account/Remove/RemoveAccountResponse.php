<?php

namespace App\Security\Application\Account\Remove;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveAccountResponse implements CommandResponse
{
    public function __construct(public AccountId $accountId)
    {
        
    }
}