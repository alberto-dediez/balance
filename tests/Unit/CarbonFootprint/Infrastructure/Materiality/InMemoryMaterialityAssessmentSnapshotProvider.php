<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Materiality;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshot;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshotProvider;

class InMemoryMaterialityAssessmentSnapshotProvider implements MaterialityAssessmentSnapshotProvider
{
    /** @param array<string, MaterialityAssessmentSnapshot> $snapshots */
    public function __construct(
        private array $snapshots = [],
    ) {}

    public function add(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId, MaterialityAssessmentSnapshot $snapshot): void
    {
        $this->snapshots[$this->key($subjectType, $carbonFootprintId)] = $snapshot;
    }

    public function find(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): ?MaterialityAssessmentSnapshot
    {
        return $this->snapshots[$this->key($subjectType, $carbonFootprintId)] ?? null;
    }

    private function key(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): string
    {
        return sprintf('%s|%s', $subjectType->value, $carbonFootprintId);
    }
}
