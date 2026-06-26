<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\Material\MaterialFilter;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryMaterialRepository implements MaterialRepository
{
    /** @param array<string, Material> $materials */
    public function __construct(
        private array $materials = [],
    ) {}

    public function add(Material $material): void
    {
        if (isset($this->materials[(string) $material->id()])) {
            return;
        }

        $this->materials[(string) $material->id()] = $material;
    }

    public function remove(Material $material): void
    {
        if (!isset($this->materials[(string) $material->id()])) {
            return;
        }

        unset($this->materials[(string) $material->id()]);
    }

    public function find(MaterialId $materialId): ?Material
    {
        return (isset($this->materials[(string) $materialId]) && false == $this->materials[(string) $materialId]->isRemoved())
            ? $this->materials[(string) $materialId]
            : null
        ;
    }

    public function findOneByName(string $name): ?Material
    {
        foreach ($this->materials as $material) {
            if (true == $material->isRemoved()) {
                continue;
            }

            if (strtolower(trim((string) $material->name())) === strtolower(trim($name))) {
                return $material;
            }
        }

        return null;
    }

    /** @return array<int, Material> */
    public function findByFilter(MaterialFilter $filter): array
    {
        $results = [];

        if (0 === count($this->materials)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->materials as $material) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $material->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (null !== $filter->category && $material->category() !== $filter->category) {
                continue;
            }

            if (true == $material->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $material;
        }

        return $results;
    }

    public function totalResultsByFilter(MaterialFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
