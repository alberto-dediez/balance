<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

use App\Security\Domain\Model\Account\Account;
use App\Shared\Domain\Model\Filter;

class UserFilter
{
    use Filter;

    public function __construct(
        public ?string $username = null,
        public ?Role $role = null,
        public ?Account $account = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
