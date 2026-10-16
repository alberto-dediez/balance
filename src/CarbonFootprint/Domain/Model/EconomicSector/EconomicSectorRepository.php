<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EconomicSector;

interface EconomicSectorRepository
{
    public function add(EconomicSector $economicSector): void;

    public function remove(EconomicSector $economicSector): void;

    public function find(EconomicSectorId $economicSectorId): ?EconomicSector;

    public function findByClassificationCode(string $code): ?EconomicSector;

    /** @return array<EconomicSector> */
    public function findByFilter(EconomicSectorFilter $filter): array;

    public function totalResultsByFilter(EconomicSectorFilter $filter): int;
}
