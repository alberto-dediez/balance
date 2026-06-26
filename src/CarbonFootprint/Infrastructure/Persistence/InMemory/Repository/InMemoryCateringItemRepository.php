<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemFilter;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryCateringItemRepository implements CateringItemRepository
{
    /** @param array<string, CateringItem> $cateringItems */
    public function __construct(
        private array $cateringItems = [],
    ) {}

    public function add(CateringItem $cateringItem): void
    {
        if (isset($this->cateringItems[(string) $cateringItem->id()])) {
            return;
        }

        $this->cateringItems[(string) $cateringItem->id()] = $cateringItem;
    }

    public function remove(CateringItem $cateringItem): void
    {
        if (!isset($this->cateringItems[(string) $cateringItem->id()])) {
            return;
        }

        unset($this->cateringItems[(string) $cateringItem->id()]);
    }

    public function find(CateringItemId $cateringItemId): ?CateringItem
    {
        return (isset($this->cateringItems[(string) $cateringItemId]) && false == $this->cateringItems[(string) $cateringItemId]->isRemoved())
            ? $this->cateringItems[(string) $cateringItemId]
            : null
        ;
    }

    /** @return array<int, CateringItem> */
    public function findByFilter(CateringItemFilter $filter): array
    {
        $results = [];

        if (0 === count($this->cateringItems)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->cateringItems as $cateringItem) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $cateringItem->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (null !== $filter->category && $cateringItem->category() !== $filter->category) {
                continue;
            }

            if (true == $cateringItem->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $cateringItem;
        }

        return $results;
    }

    public function totalResultsByFilter(CateringItemFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
