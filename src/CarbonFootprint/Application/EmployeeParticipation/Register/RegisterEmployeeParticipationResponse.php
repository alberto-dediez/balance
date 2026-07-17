<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeParticipation\Register;

use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipation;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterEmployeeParticipationResponse implements CommandResponse
{
    public function __construct(
        public EmployeeParticipation $employeeParticipation,
    ) {}
}
