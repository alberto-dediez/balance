<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Persistence\InMemory\Repository;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorFilter;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryMaterialitySectorRepository implements MaterialitySectorRepository
{
    /** @param array<string, MaterialitySector> $materialitySectors */
    public function __construct(
        private array $materialitySectors = [],
    ) {}

    public function add(MaterialitySector $materialitySector): void
    {
        $this->materialitySectors[(string) $materialitySector->id()] = $materialitySector;
    }

    public function remove(MaterialitySector $materialitySector): void
    {
        unset($this->materialitySectors[(string) $materialitySector->id()]);
    }

    public function find(MaterialitySectorId $materialitySectorId): ?MaterialitySector
    {
        $materialitySector = $this->materialitySectors[(string) $materialitySectorId] ?? null;

        if (null === $materialitySector || false != $materialitySector->isRemoved()) {
            return null;
        }

        return $materialitySector;
    }

    /** @return array<int, MaterialitySector> */
    public function findByFilter(MaterialitySectorFilter $filter): array
    {
        $matching = [];

        foreach ($this->materialitySectors as $materialitySector) {
            if (!$this->matches($materialitySector, $filter)) {
                continue;
            }

            $matching[] = $materialitySector;
        }

        $this->sort($matching, $filter);

        if ($filter->limit > 0) {
            return array_slice($matching, $filter->page * $filter->limit, $filter->limit);
        }

        return $matching;
    }

    public function totalResultsByFilter(MaterialitySectorFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }

    /** @return list<MaterialitySector> */
    public function findAll(): array
    {
        return array_values($this->findByFilter(new MaterialitySectorFilter(limit: 0)));
    }

    private function matches(MaterialitySector $materialitySector, MaterialitySectorFilter $filter): bool
    {
        if (!$filter->includeRemoved && false != $materialitySector->isRemoved()) {
            return false;
        }

        if (null !== $filter->name && '' !== $filter->name && !str_contains(mb_strtolower($materialitySector->name()), mb_strtolower($filter->name))) {
            return false;
        }

        return true;
    }

    /** @param list<MaterialitySector> $materialitySectors */
    private function sort(array &$materialitySectors, MaterialitySectorFilter $filter): void
    {
        $way = 'ASC' === $filter->orderWay ? 1 : -1;

        usort($materialitySectors, static function (MaterialitySector $a, MaterialitySector $b) use ($filter, $way): int {
            $comparison = match ($filter->orderBy) {
                'registeredAt' => $a->registeredAt() <=> $b->registeredAt(),
                'updatedAt' => $a->updatedAt() <=> $b->updatedAt(),
                default => strcmp($a->name(), $b->name()),
            };

            return 0 !== $comparison ? $comparison * $way : strcmp((string) $a->id(), (string) $b->id());
        });
    }
}
