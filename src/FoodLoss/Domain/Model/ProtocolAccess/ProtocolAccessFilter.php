<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\ProtocolAccess;

use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class ProtocolAccessFilter
{
    use Filter;

    public function __construct(
        public ?ProtocolId $protocolId = null,
        public ?EmployeeId $employeeId = null,
        public ?AccountId $accountId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
