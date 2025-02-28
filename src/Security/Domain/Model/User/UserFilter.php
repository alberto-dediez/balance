<?php

namespace App\Security\Domain\Model\User;

use App\Shared\Domain\Model\Filter;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\Account\Account;

class UserFilter
{
    use Filter;
    
    public function __construct(
        public ?string $username = null,
        public ?Role $role = null,
        public ?Account $account = null
    ) {

    }
}