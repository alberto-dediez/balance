<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGood;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodFilter;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryCapitalGoodRepository implements CapitalGoodRepository
{
    /** @param array<string, CapitalGood> $capitalGoods */
    public function __construct(
        private array $capitalGoods = [],
    ) {}

    public function add(CapitalGood $capitalGood): void
    {
        if (isset($this->capitalGoods[(string) $capitalGood->id()])) {
            return;
        }

        $this->capitalGoods[(string) $capitalGood->id()] = $capitalGood;
    }

    public function remove(CapitalGood $capitalGood): void
    {
        if (!isset($this->capitalGoods[(string) $capitalGood->id()])) {
            return;
        }

        unset($this->capitalGoods[(string) $capitalGood->id()]);
    }

    public function find(CapitalGoodId $capitalGoodId): ?CapitalGood
    {
        return (isset($this->capitalGoods[(string) $capitalGoodId]) && false == $this->capitalGoods[(string) $capitalGoodId]->isRemoved())
            ? $this->capitalGoods[(string) $capitalGoodId]
            : null
        ;
    }

    /** @return array<int, CapitalGood> */
    public function findByFilter(CapitalGoodFilter $filter): array
    {
        $results = [];

        if (0 === count($this->capitalGoods)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        $way = 'ASC' === $filter->orderWay ? 1 : -1;
        $capitalGoods = array_values($this->capitalGoods);
        usort(
            $capitalGoods,
            static fn(CapitalGood $a, CapitalGood $b): int => $way * match ($filter->orderBy) {
                'registeredAt' => $a->registeredAt() <=> $b->registeredAt(),
                'updatedAt' => $a->updatedAt() <=> $b->updatedAt(),
                default => $a->name() <=> $b->name(),
            },
        );

        foreach ($capitalGoods as $capitalGood) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $capitalGood->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (null !== $filter->category && $capitalGood->category() !== $filter->category) {
                continue;
            }

            if (true == $capitalGood->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $capitalGood;
        }

        return $results;
    }

    public function totalResultsByFilter(CapitalGoodFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
