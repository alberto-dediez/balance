<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\MitigationAction;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;

final class MitigationPlanStepRoute
{
    /** @return array{route: string, params: array<string, string>} */
    public function resolve(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): array
    {
        return match ($subjectType) {
            CarbonFootprintReportSubjectType::Property => [
                'route' => 'property_carbon_footprints_mitigation_plan',
                'params' => ['propertyCarbonFootprintId' => $carbonFootprintId],
            ],
            CarbonFootprintReportSubjectType::Company => [
                'route' => 'company_carbon_footprints_mitigation_plan',
                'params' => ['companyCarbonFootprintId' => $carbonFootprintId],
            ],
            CarbonFootprintReportSubjectType::Event => throw new \InvalidArgumentException('Mitigation plans are not available for event carbon footprints'),
        };
    }
}
