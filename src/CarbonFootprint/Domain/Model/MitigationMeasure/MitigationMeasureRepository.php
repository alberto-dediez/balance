<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationMeasure;

interface MitigationMeasureRepository
{
    public function add(MitigationMeasure $mitigationMeasure): void;

    public function remove(MitigationMeasure $mitigationMeasure): void;

    public function find(MitigationMeasureId $mitigationMeasureId): ?MitigationMeasure;

    /** @return array<MitigationMeasure> */
    public function findByFilter(MitigationMeasureFilter $filter): array;

    public function totalResultsByFilter(MitigationMeasureFilter $filter): int;
}
