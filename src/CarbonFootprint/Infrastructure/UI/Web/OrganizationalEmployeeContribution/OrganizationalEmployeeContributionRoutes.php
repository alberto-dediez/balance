<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;

final class OrganizationalEmployeeContributionRoutes
{
    public static function step(CarbonFootprintReportSubjectType $subjectType): string
    {
        return self::prefix($subjectType) . '_employees';
    }

    /** @return array<string, string> */
    public static function stepParameters(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): array
    {
        return match ($subjectType) {
            CarbonFootprintReportSubjectType::Property => ['propertyCarbonFootprintId' => $carbonFootprintId],
            CarbonFootprintReportSubjectType::Company => ['companyCarbonFootprintId' => $carbonFootprintId],
            CarbonFootprintReportSubjectType::Event => throw new \InvalidArgumentException('Employee contributions of organizational footprints are not available for events'),
        };
    }

    private static function prefix(CarbonFootprintReportSubjectType $subjectType): string
    {
        return match ($subjectType) {
            CarbonFootprintReportSubjectType::Property => 'property_carbon_footprints',
            CarbonFootprintReportSubjectType::Company => 'company_carbon_footprints',
            CarbonFootprintReportSubjectType::Event => throw new \InvalidArgumentException('Employee contributions of organizational footprints are not available for events'),
        };
    }
}
