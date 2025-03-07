<?php

namespace App\Security\Application\Account\Remove;

use App\Security\Domain\Model\Account\AccountId;

class RemoveAccountResponse
{
    public function __construct(public AccountId $accountId)
    {
        
    }
}