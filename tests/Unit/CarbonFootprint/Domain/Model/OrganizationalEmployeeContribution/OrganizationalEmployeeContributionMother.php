<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\CommuteAnswer;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionId;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\TripAnswer;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

class OrganizationalEmployeeContributionMother
{
    public static function registered(): OrganizationalEmployeeContribution
    {
        return self::from(
            subjectType: CarbonFootprintReportSubjectType::Property,
            carbonFootprintId: (string) OrganizationalEmployeeContributionId::generate(),
            accountId: 'account-id',
            employeeId: (string) EmployeeId::generate(),
            id: (string) OrganizationalEmployeeContributionId::generate(),
        );
    }

    public static function forFootprint(
        CarbonFootprintReportSubjectType $subjectType,
        string $carbonFootprintId,
        string $accountId = 'account-id',
        ?string $employeeId = null,
        ?string $token = null,
    ): OrganizationalEmployeeContribution {
        return self::from(
            subjectType: $subjectType,
            carbonFootprintId: $carbonFootprintId,
            accountId: $accountId,
            employeeId: $employeeId ?? (string) EmployeeId::generate(),
            id: (string) OrganizationalEmployeeContributionId::generate(),
            token: $token,
        );
    }

    public static function answered(
        CarbonFootprintReportSubjectType $subjectType,
        string $carbonFootprintId,
        ?string $commuteMobilityId,
        float $commuteKm = 12.0,
        int $daysPerWeek = 5,
        int $weeksPerYear = 46,
        int $commuteOccupants = 1,
        ?string $tripMobilityId = null,
        float $tripKm = 1200.0,
        int $trips = 1,
        int $tripOccupants = 1,
        int $hotelNights = 0,
        ?string $workplacePropertyId = null,
        string $accountId = 'account-id',
    ): OrganizationalEmployeeContribution {
        $contribution = self::forFootprint($subjectType, $carbonFootprintId, $accountId);

        $contribution->answerCommuting(
            null !== $workplacePropertyId ? new PropertyId($workplacePropertyId) : null,
            null !== $commuteMobilityId ? $weeksPerYear : null,
            null !== $commuteMobilityId ? [new CommuteAnswer(new MobilityId($commuteMobilityId), $commuteKm, $daysPerWeek, $commuteOccupants)] : [],
        );
        $contribution->answerBusinessTravel(
            null !== $tripMobilityId ? [new TripAnswer(new MobilityId($tripMobilityId), $tripKm, $trips, $tripOccupants, $hotelNights)] : [],
        );

        return $contribution;
    }

    public static function from(
        CarbonFootprintReportSubjectType $subjectType,
        string $carbonFootprintId,
        string $accountId,
        string $employeeId,
        string $id,
        ?string $employeeName = 'Employee Name',
        ?string $token = null,
    ): OrganizationalEmployeeContribution {
        $contribution = new OrganizationalEmployeeContribution(id: new OrganizationalEmployeeContributionId($id));
        $contribution->register(
            subjectType: $subjectType,
            carbonFootprintId: $carbonFootprintId,
            accountId: new AccountId($accountId),
            employeeId: new EmployeeId($employeeId),
            token: $token ?? OrganizationalEmployeeContribution::generateToken(),
            employeeName: $employeeName,
        );

        return $contribution;
    }
}
