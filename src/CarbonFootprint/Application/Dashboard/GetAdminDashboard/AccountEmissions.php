<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Dashboard\GetAdminDashboard;

use App\Security\Domain\Model\Account\Account;

final class AccountEmissions
{
    public function __construct(
        public readonly Account $account,
        public readonly float $co2eq,
    ) {}
}
