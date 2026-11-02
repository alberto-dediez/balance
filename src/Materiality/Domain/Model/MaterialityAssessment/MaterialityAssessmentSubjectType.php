<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityAssessment;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;

enum MaterialityAssessmentSubjectType: string
{
    case Property = 'property';

    case Company = 'company';

    public function toReportSubjectType(): CarbonFootprintReportSubjectType
    {
        return CarbonFootprintReportSubjectType::from($this->value);
    }
}
