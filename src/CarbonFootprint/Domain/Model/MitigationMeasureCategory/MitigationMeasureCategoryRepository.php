<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationMeasureCategory;

interface MitigationMeasureCategoryRepository
{
    public function add(MitigationMeasureCategory $mitigationMeasureCategory): void;

    public function remove(MitigationMeasureCategory $mitigationMeasureCategory): void;

    public function find(MitigationMeasureCategoryId $mitigationMeasureCategoryId): ?MitigationMeasureCategory;

    /** @return array<MitigationMeasureCategory> */
    public function findByFilter(MitigationMeasureCategoryFilter $filter): array;

    public function totalResultsByFilter(MitigationMeasureCategoryFilter $filter): int;
}
