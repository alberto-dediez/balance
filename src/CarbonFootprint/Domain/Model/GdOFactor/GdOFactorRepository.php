<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\GdOFactor;

interface GdOFactorRepository
{
    public function add(GdOFactor $gdOFactor): void;

    public function remove(GdOFactor $gdOFactor): void;

    public function find(GdOFactorId $gdOFactorId): ?GdOFactor;

    /** @return array<GdOFactor> */
    public function findByFilter(GdOFactorFilter $filter): array;

    public function totalResultsByFilter(GdOFactorFilter $filter): int;
}
