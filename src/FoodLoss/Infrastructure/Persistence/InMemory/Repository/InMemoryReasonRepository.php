<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\Reason\Reason;
use App\FoodLoss\Domain\Model\Reason\ReasonFilter;
use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryReasonRepository implements ReasonRepository
{
    /** @param array<string, Reason> $reasons */
    public function __construct(
        private array $reasons = [],
    ) {}

    public function add(Reason $reason): void
    {
        if (isset($this->reasons[(string) $reason->id()])) {
            return;
        }

        $this->reasons[(string) $reason->id()] = $reason;
    }

    public function remove(Reason $reason): void
    {
        if (!isset($this->reasons[(string) $reason->id()])) {
            return;
        }

        unset($this->reasons[(string) $reason->id()]);
    }

    public function find(ReasonId $reasonId): ?Reason
    {
        return (isset($this->reasons[(string) $reasonId]) && false == $this->reasons[(string) $reasonId]->isRemoved())
            ? $this->reasons[(string) $reasonId]
            : null
        ;
    }

    /** @return array<int, Reason> */
    public function findByFilter(ReasonFilter $filter): array
    {
        $results = [];

        if (0 === count($this->reasons)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->reasons as $reason) {
            if (
                !empty($filter->description)
                && !str_contains(strtolower((string) $reason->description()), strtolower($filter->description))
            ) {
                continue;
            }

            if (
                !empty($filter->priority)
                && !str_contains(strtolower($reason->priority()->value), strtolower($filter->priority->value))
            ) {
                continue;
            }

            if (true == $reason->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $reason;
        }

        return $results;
    }

    public function totalResultsByFilter(ReasonFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
