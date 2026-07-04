<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Dashboard\GetOwnerDashboard;

use App\Security\Domain\Model\Account\AccountId;

class GetOwnerDashboardRequest
{
    public function __construct(
        public readonly AccountId $accountId,
    ) {}
}
