<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;

final class OrganizationalProviderContributionRoutes
{
    public static function step(CarbonFootprintReportSubjectType $subjectType): string
    {
        return self::prefix($subjectType) . '_providers';
    }

    /** @return array<string, string> */
    public static function stepParameters(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): array
    {
        return match ($subjectType) {
            CarbonFootprintReportSubjectType::Property => ['propertyCarbonFootprintId' => $carbonFootprintId],
            CarbonFootprintReportSubjectType::Company => ['companyCarbonFootprintId' => $carbonFootprintId],
            CarbonFootprintReportSubjectType::Event => throw new \InvalidArgumentException('Provider contributions of organizational footprints are not available for events'),
        };
    }

    public static function action(CarbonFootprintReportSubjectType $subjectType, string $action): string
    {
        return self::step($subjectType) . '_' . $action;
    }

    private static function prefix(CarbonFootprintReportSubjectType $subjectType): string
    {
        return match ($subjectType) {
            CarbonFootprintReportSubjectType::Property => 'property_carbon_footprints',
            CarbonFootprintReportSubjectType::Company => 'company_carbon_footprints',
            CarbonFootprintReportSubjectType::Event => throw new \InvalidArgumentException('Provider contributions of organizational footprints are not available for events'),
        };
    }
}
