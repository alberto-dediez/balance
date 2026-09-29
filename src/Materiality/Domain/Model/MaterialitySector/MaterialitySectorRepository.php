<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialitySector;

interface MaterialitySectorRepository
{
    public function add(MaterialitySector $materialitySector): void;

    public function remove(MaterialitySector $materialitySector): void;

    public function find(MaterialitySectorId $materialitySectorId): ?MaterialitySector;

    /** @return array<MaterialitySector> */
    public function findByFilter(MaterialitySectorFilter $filter): array;

    public function totalResultsByFilter(MaterialitySectorFilter $filter): int;

    /** @return list<MaterialitySector> */
    public function findAll(): array;
}
