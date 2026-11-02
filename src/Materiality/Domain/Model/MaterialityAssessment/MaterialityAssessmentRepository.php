<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityAssessment;

interface MaterialityAssessmentRepository
{
    public function add(MaterialityAssessment $materialityAssessment): void;

    public function remove(MaterialityAssessment $materialityAssessment): void;

    public function find(MaterialityAssessmentId $materialityAssessmentId): ?MaterialityAssessment;

    public function findCurrentByCarbonFootprint(MaterialityAssessmentSubjectType $subjectType, string $carbonFootprintId): ?MaterialityAssessment;

    /** @return array<MaterialityAssessment> */
    public function findByFilter(MaterialityAssessmentFilter $filter): array;

    public function totalResultsByFilter(MaterialityAssessmentFilter $filter): int;
}
