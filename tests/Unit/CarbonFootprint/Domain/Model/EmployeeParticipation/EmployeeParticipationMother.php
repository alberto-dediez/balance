<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeParticipation;

use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipation;
use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Security\Domain\Model\Account\AccountId;

class EmployeeParticipationMother
{
    public static function registeredEmployeeParticipation(): EmployeeParticipation
    {
        return self::fromEventCarbonFootprintId((string) EventCarbonFootprintId::generate());
    }

    public static function fromEventCarbonFootprintId(string $eventCarbonFootprintId): EmployeeParticipation
    {
        return self::from(
            eventCarbonFootprintId: $eventCarbonFootprintId,
            id: (string) EmployeeParticipationId::generate(),
        );
    }

    public static function fromEventCarbonFootprintIdAndAccount(string $eventCarbonFootprintId, string $accountId): EmployeeParticipation
    {
        return self::from(
            eventCarbonFootprintId: $eventCarbonFootprintId,
            id: (string) EmployeeParticipationId::generate(),
            accountId: $accountId,
        );
    }

    public static function from(
        string $eventCarbonFootprintId,
        string $id,
        ?string $accountId = null,
    ): EmployeeParticipation {
        $employeeParticipation = new EmployeeParticipation(id: new EmployeeParticipationId($id));
        $employeeParticipation->register(
            eventCarbonFootprintId: new EventCarbonFootprintId($eventCarbonFootprintId),
            accountId: new AccountId($accountId ?? 'account-id'),
        );

        return $employeeParticipation;
    }

    public static function random(): EmployeeParticipation
    {
        return self::registeredEmployeeParticipation();
    }
}
