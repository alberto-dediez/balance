<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationAction;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionFilter;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionId;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryMitigationActionRepository implements MitigationActionRepository
{
    /** @param array<string, MitigationAction> $mitigationActions */
    public function __construct(
        private array $mitigationActions = [],
    ) {}

    public function add(MitigationAction $mitigationAction): void
    {
        if (isset($this->mitigationActions[(string) $mitigationAction->id()])) {
            return;
        }

        $this->mitigationActions[(string) $mitigationAction->id()] = $mitigationAction;
    }

    public function remove(MitigationAction $mitigationAction): void
    {
        if (!isset($this->mitigationActions[(string) $mitigationAction->id()])) {
            return;
        }

        unset($this->mitigationActions[(string) $mitigationAction->id()]);
    }

    public function find(MitigationActionId $mitigationActionId): ?MitigationAction
    {
        return (isset($this->mitigationActions[(string) $mitigationActionId]) && false == $this->mitigationActions[(string) $mitigationActionId]->isRemoved())
            ? $this->mitigationActions[(string) $mitigationActionId]
            : null
        ;
    }

    /** @return array<int, MitigationAction> */
    public function findByFilter(MitigationActionFilter $filter): array
    {
        $results = [];

        if (0 === count($this->mitigationActions)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        $mitigationActions = array_values($this->mitigationActions);
        usort(
            $mitigationActions,
            static fn(MitigationAction $a, MitigationAction $b): int => $a->registeredAt() <=> $b->registeredAt(),
        );

        foreach ($mitigationActions as $mitigationAction) {
            if (null !== $filter->subjectType && $mitigationAction->subjectType() !== $filter->subjectType) {
                continue;
            }

            if (null !== $filter->carbonFootprintId && $mitigationAction->carbonFootprintId() !== $filter->carbonFootprintId) {
                continue;
            }

            if (null !== $filter->accountId && (null === $mitigationAction->accountId() || !$filter->accountId->equals($mitigationAction->accountId()))) {
                continue;
            }

            if (true == $mitigationAction->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $mitigationAction;
        }

        return $results;
    }

    public function totalResultsByFilter(MitigationActionFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
