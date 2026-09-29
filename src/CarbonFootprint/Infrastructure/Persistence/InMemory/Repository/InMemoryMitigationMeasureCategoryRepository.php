<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategory;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryFilter;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryMitigationMeasureCategoryRepository implements MitigationMeasureCategoryRepository
{
    /** @param array<string, MitigationMeasureCategory> $mitigationMeasureCategorys */
    public function __construct(
        private array $mitigationMeasureCategorys = [],
    ) {}

    public function add(MitigationMeasureCategory $mitigationMeasureCategory): void
    {
        if (isset($this->mitigationMeasureCategorys[(string) $mitigationMeasureCategory->id()])) {
            return;
        }

        $this->mitigationMeasureCategorys[(string) $mitigationMeasureCategory->id()] = $mitigationMeasureCategory;
    }

    public function remove(MitigationMeasureCategory $mitigationMeasureCategory): void
    {
        if (!isset($this->mitigationMeasureCategorys[(string) $mitigationMeasureCategory->id()])) {
            return;
        }

        unset($this->mitigationMeasureCategorys[(string) $mitigationMeasureCategory->id()]);
    }

    public function find(MitigationMeasureCategoryId $mitigationMeasureCategoryId): ?MitigationMeasureCategory
    {
        return (isset($this->mitigationMeasureCategorys[(string) $mitigationMeasureCategoryId]) && false == $this->mitigationMeasureCategorys[(string) $mitigationMeasureCategoryId]->isRemoved())
            ? $this->mitigationMeasureCategorys[(string) $mitigationMeasureCategoryId]
            : null
        ;
    }

    /** @return array<int, MitigationMeasureCategory> */
    public function findByFilter(MitigationMeasureCategoryFilter $filter): array
    {
        $results = [];

        if (0 === count($this->mitigationMeasureCategorys)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        $way = 'ASC' === $filter->orderWay ? 1 : -1;
        $mitigationMeasureCategorys = array_values($this->mitigationMeasureCategorys);
        usort(
            $mitigationMeasureCategorys,
            static fn(MitigationMeasureCategory $a, MitigationMeasureCategory $b): int => $way * match ($filter->orderBy) {
                'registeredAt' => $a->registeredAt() <=> $b->registeredAt(),
                default => $a->name() <=> $b->name(),
            },
        );

        foreach ($mitigationMeasureCategorys as $mitigationMeasureCategory) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower($mitigationMeasureCategory->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (true == $mitigationMeasureCategory->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $mitigationMeasureCategory;
        }

        return $results;
    }

    public function totalResultsByFilter(MitigationMeasureCategoryFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
