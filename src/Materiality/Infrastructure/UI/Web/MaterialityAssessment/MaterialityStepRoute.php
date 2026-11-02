<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\MaterialityAssessment;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;

final class MaterialityStepRoute
{
    /** @return array{route: string, params: array<string, string>} */
    public function resolve(MaterialityAssessmentSubjectType $subjectType, string $carbonFootprintId): array
    {
        return match ($subjectType) {
            MaterialityAssessmentSubjectType::Property => [
                'route' => 'property_carbon_footprints_materiality',
                'params' => ['propertyCarbonFootprintId' => $carbonFootprintId],
            ],
            MaterialityAssessmentSubjectType::Company => [
                'route' => 'company_carbon_footprints_materiality',
                'params' => ['companyCarbonFootprintId' => $carbonFootprintId],
            ],
        };
    }
}
