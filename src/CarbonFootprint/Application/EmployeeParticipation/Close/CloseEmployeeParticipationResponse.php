<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeParticipation\Close;

use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipation;
use App\Shared\Application\CommandBus\CommandResponse;

class CloseEmployeeParticipationResponse implements CommandResponse
{
    public function __construct(
        public EmployeeParticipation $employeeParticipation,
        public bool $overnightStayCatalogMissing = false,
    ) {}
}
