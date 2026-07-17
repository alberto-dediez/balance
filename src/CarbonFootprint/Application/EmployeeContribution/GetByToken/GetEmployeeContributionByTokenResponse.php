<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution\GetByToken;

use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContribution;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\EventOperations\Domain\Model\Event\Event;

final class GetEmployeeContributionByTokenResponse
{
    /** @param array<Mobility> $mobilities */
    public function __construct(
        public readonly EmployeeContribution $employeeContribution,
        public readonly EventCarbonFootprint $eventCarbonFootprint,
        public readonly Event $event,
        public readonly array $mobilities,
    ) {}
}
