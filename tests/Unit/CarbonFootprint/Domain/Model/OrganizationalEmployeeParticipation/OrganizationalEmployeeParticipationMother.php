<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipation;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationId;
use App\Security\Domain\Model\Account\AccountId;

class OrganizationalEmployeeParticipationMother
{
    public static function registered(): OrganizationalEmployeeParticipation
    {
        return self::from(CarbonFootprintReportSubjectType::Property, (string) OrganizationalEmployeeParticipationId::generate(), (string) OrganizationalEmployeeParticipationId::generate());
    }

    public static function withTotal(
        CarbonFootprintReportSubjectType $subjectType,
        string $carbonFootprintId,
        int $totalEmployees,
        string $accountId = 'account-id',
        ConfidenceLevel $confidenceLevel = ConfidenceLevel::NinetyFive,
    ): OrganizationalEmployeeParticipation {
        $participation = self::from($subjectType, $carbonFootprintId, (string) OrganizationalEmployeeParticipationId::generate(), $accountId);
        $participation->update($totalEmployees, $confidenceLevel);

        return $participation;
    }

    public static function from(
        CarbonFootprintReportSubjectType $subjectType,
        string $carbonFootprintId,
        string $id,
        string $accountId = 'account-id',
    ): OrganizationalEmployeeParticipation {
        $participation = new OrganizationalEmployeeParticipation(id: new OrganizationalEmployeeParticipationId($id));
        $participation->register(
            subjectType: $subjectType,
            carbonFootprintId: $carbonFootprintId,
            accountId: new AccountId($accountId),
        );

        return $participation;
    }
}
