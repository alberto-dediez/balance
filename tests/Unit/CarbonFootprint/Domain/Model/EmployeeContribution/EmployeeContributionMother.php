<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeContribution;

use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContribution;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\Account\AccountId;

class EmployeeContributionMother
{
    public static function registeredEmployeeContribution(): EmployeeContribution
    {
        return self::from(
            eventCarbonFootprintId: (string) EventCarbonFootprintId::generate(),
            accountId: 'account-id',
            employeeId: (string) EmployeeId::generate(),
            id: (string) EmployeeContributionId::generate(),
        );
    }

    public static function fromEventCarbonFootprintIdAndAccount(string $eventCarbonFootprintId, string $accountId): EmployeeContribution
    {
        return self::from(
            eventCarbonFootprintId: $eventCarbonFootprintId,
            accountId: $accountId,
            employeeId: (string) EmployeeId::generate(),
            id: (string) EmployeeContributionId::generate(),
        );
    }

    public static function fromEventCarbonFootprintIdAndEmployeeId(string $eventCarbonFootprintId, string $employeeId): EmployeeContribution
    {
        return self::from(
            eventCarbonFootprintId: $eventCarbonFootprintId,
            accountId: 'account-id',
            employeeId: $employeeId,
            id: (string) EmployeeContributionId::generate(),
        );
    }

    public static function answeredForAllPhases(
        string $eventCarbonFootprintId,
        string $mobilityId,
        float $distanceKm = 100.0,
        int $vehicleOccupants = 1,
        int $days = 1,
        int $hotelNights = 0,
    ): EmployeeContribution {
        $employeeContribution = self::fromEventCarbonFootprintIdAndEmployeeId($eventCarbonFootprintId, (string) EmployeeId::generate());

        foreach ([Phase::Assembly, Phase::Celebration, Phase::Breakdown] as $phase) {
            $employeeContribution->answerPhase(
                phase: $phase,
                worked: true,
                mobilityId: new MobilityId($mobilityId),
                distanceKm: $distanceKm,
                vehicleOccupants: $vehicleOccupants,
                days: $days,
                hotelNights: $hotelNights,
            );
        }

        return $employeeContribution;
    }

    public static function from(
        string $eventCarbonFootprintId,
        string $accountId,
        string $employeeId,
        string $id,
        ?string $employeeName = 'Employee Name',
    ): EmployeeContribution {
        $employeeContribution = new EmployeeContribution(id: new EmployeeContributionId($id));
        $employeeContribution->register(
            eventCarbonFootprintId: new EventCarbonFootprintId($eventCarbonFootprintId),
            accountId: new AccountId($accountId),
            employeeId: new EmployeeId($employeeId),
            token: EmployeeContribution::generateToken(),
            employeeName: $employeeName,
        );

        return $employeeContribution;
    }

    public static function random(): EmployeeContribution
    {
        return self::registeredEmployeeContribution();
    }
}
