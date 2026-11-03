<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

interface MaterialityAssessmentSnapshotProvider
{
    public function find(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): ?MaterialityAssessmentSnapshot;
}
