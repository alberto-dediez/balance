<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Material;

interface MaterialRepository
{
    public function add(Material $material): void;

    public function remove(Material $material): void;

    public function find(MaterialId $materialId): ?Material;

    public function findOneByName(string $name): ?Material;

    /** @return array<Material> */
    public function findByFilter(MaterialFilter $filter): array;

    public function totalResultsByFilter(MaterialFilter $filter): int;
}
