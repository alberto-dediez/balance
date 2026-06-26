<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Combustible;

interface CombustibleRepository
{
    public function add(Combustible $combustible): void;

    public function remove(Combustible $combustible): void;

    public function find(CombustibleId $combustibleId): ?Combustible;

    public function findOneByName(string $name): ?Combustible;

    /** @return array<Combustible> */
    public function findByFilter(CombustibleFilter $filter): array;

    public function totalResultsByFilter(CombustibleFilter $filter): int;
}
