<?php

namespace App\Security\Domain\Model\User;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\Account\Account;

class UserFilter
{
    public function __construct(
        public ?string $username = null,
        public ?Role $role = null,
        public ?Account $account = null,
        public int $page = 0,
        public int $limit = 10,
        public string $orderBy = 'default',
        public string $orderWay = 'ASC'
    ) {

    }
}