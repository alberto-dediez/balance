<?php

namespace App\Security\Application\UpdateAccount;

use App\Security\Domain\Model\Account\AccountId;

class UpdateAccountRequest
{
    public function __construct(public AccountId $accountId, public string $name, public bool $active)
    {

    }
}