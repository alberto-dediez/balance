<?php

declare(strict_types=1);

namespace App\HumanResources\Domain\Model\Employee;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\Filter;

class EmployeeFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?AccountId $accountId = null,
        public ?UserId $userId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
