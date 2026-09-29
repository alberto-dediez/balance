<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasure;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureFilter;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryMitigationMeasureRepository implements MitigationMeasureRepository
{
    /** @param array<string, MitigationMeasure> $mitigationMeasures */
    public function __construct(
        private array $mitigationMeasures = [],
    ) {}

    public function add(MitigationMeasure $mitigationMeasure): void
    {
        if (isset($this->mitigationMeasures[(string) $mitigationMeasure->id()])) {
            return;
        }

        $this->mitigationMeasures[(string) $mitigationMeasure->id()] = $mitigationMeasure;
    }

    public function remove(MitigationMeasure $mitigationMeasure): void
    {
        if (!isset($this->mitigationMeasures[(string) $mitigationMeasure->id()])) {
            return;
        }

        unset($this->mitigationMeasures[(string) $mitigationMeasure->id()]);
    }

    public function find(MitigationMeasureId $mitigationMeasureId): ?MitigationMeasure
    {
        return (isset($this->mitigationMeasures[(string) $mitigationMeasureId]) && false == $this->mitigationMeasures[(string) $mitigationMeasureId]->isRemoved())
            ? $this->mitigationMeasures[(string) $mitigationMeasureId]
            : null
        ;
    }

    /** @return array<int, MitigationMeasure> */
    public function findByFilter(MitigationMeasureFilter $filter): array
    {
        $results = [];

        if (0 === count($this->mitigationMeasures)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        $way = 'ASC' === $filter->orderWay ? 1 : -1;
        $mitigationMeasures = array_values($this->mitigationMeasures);
        usort(
            $mitigationMeasures,
            static fn(MitigationMeasure $a, MitigationMeasure $b): int => $way * match ($filter->orderBy) {
                'registeredAt' => $a->registeredAt() <=> $b->registeredAt(),
                default => $a->name() <=> $b->name(),
            },
        );

        foreach ($mitigationMeasures as $mitigationMeasure) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower($mitigationMeasure->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (null !== $filter->categoryId && (null === $mitigationMeasure->categoryId() || !$filter->categoryId->equals($mitigationMeasure->categoryId()))) {
                continue;
            }

            if (true == $mitigationMeasure->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $mitigationMeasure;
        }

        return $results;
    }

    public function totalResultsByFilter(MitigationMeasureFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
