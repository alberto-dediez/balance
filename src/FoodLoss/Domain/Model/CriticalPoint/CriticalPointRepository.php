<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

interface CriticalPointRepository
{
    public function add(CriticalPoint $criticalPoint): void;

    public function remove(CriticalPoint $criticalPoint): void;

    public function find(CriticalPointId $criticalPointId): ?CriticalPoint;

    /** @return array<CriticalPoint> */
    public function findByFilter(CriticalPointFilter $filter): array;

    public function totalResultsByFilter(CriticalPointFilter $filter): int;
}
