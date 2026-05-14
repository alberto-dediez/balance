<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\ProtocolAccess\ListEmployeeProtocolAccessHistory;

use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\Account\AccountId;

class ListEmployeeProtocolAccessHistoryRequest
{
    public function __construct(
        public ProtocolId $protocolId,
        public EmployeeId $employeeId,
        public AccountId $accountId,
    ) {}
}
