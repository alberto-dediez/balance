<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution\Resend;

use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionId;
use App\Shared\Application\CommandBus\Command;

class ResendEmployeeContributionNotificationRequest implements Command
{
    public function __construct(
        public EmployeeContributionId $employeeContributionId,
    ) {}
}
