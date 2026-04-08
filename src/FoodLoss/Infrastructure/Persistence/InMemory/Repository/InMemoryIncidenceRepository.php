<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\Incidence\Incidence;
use App\FoodLoss\Domain\Model\Incidence\IncidenceFilter;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryIncidenceRepository implements IncidenceRepository
{
    /** @param array<string, Incidence> $incidences */
    public function __construct(
        private array $incidences = [],
    ) {}

    public function add(Incidence $incidence): void
    {
        $this->incidences[(string) $incidence->id()] = $incidence;
    }

    public function remove(Incidence $incidence): void
    {
        unset($this->incidences[(string) $incidence->id()]);
    }

    public function find(IncidenceId $incidenceId): ?Incidence
    {
        return (isset($this->incidences[(string) $incidenceId]) && false == $this->incidences[(string) $incidenceId]->isRemoved())
            ? $this->incidences[(string) $incidenceId]
            : null
        ;
    }

    public function findByFilter(IncidenceFilter $filter): array
    {
        $results = [];

        foreach ($this->incidences as $incidence) {
            if ($incidence->isRemoved()) {
                continue;
            }

            if (!empty($filter->name) && !str_contains(strtolower($incidence->name()), strtolower($filter->name))) {
                continue;
            }

            if (null !== $filter->accountId && !$incidence->accountId()?->equals($filter->accountId)) {
                continue;
            }

            $results[] = $incidence;
        }

        // Sorting (simplified)
        usort($results, fn($a, $b) => $a->name() <=> $b->name());

        if ($filter->limit > 0) {
            $results = array_slice($results, $filter->page * $filter->limit, $filter->limit);
        }

        return $results;
    }

    public function totalResultsByFilter(IncidenceFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
