<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemFilter;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryMerchandisingItemRepository implements MerchandisingItemRepository
{
    /** @param array<string, MerchandisingItem> $merchandisingItems */
    public function __construct(
        private array $merchandisingItems = [],
    ) {}

    public function add(MerchandisingItem $merchandisingItem): void
    {
        if (isset($this->merchandisingItems[(string) $merchandisingItem->id()])) {
            return;
        }

        $this->merchandisingItems[(string) $merchandisingItem->id()] = $merchandisingItem;
    }

    public function remove(MerchandisingItem $merchandisingItem): void
    {
        if (!isset($this->merchandisingItems[(string) $merchandisingItem->id()])) {
            return;
        }

        unset($this->merchandisingItems[(string) $merchandisingItem->id()]);
    }

    public function find(MerchandisingItemId $merchandisingItemId): ?MerchandisingItem
    {
        return (isset($this->merchandisingItems[(string) $merchandisingItemId]) && false == $this->merchandisingItems[(string) $merchandisingItemId]->isRemoved())
            ? $this->merchandisingItems[(string) $merchandisingItemId]
            : null
        ;
    }

    /** @return array<int, MerchandisingItem> */
    public function findByFilter(MerchandisingItemFilter $filter): array
    {
        $results = [];

        if (0 === count($this->merchandisingItems)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->merchandisingItems as $merchandisingItem) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $merchandisingItem->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (null !== $filter->category && $merchandisingItem->category() !== $filter->category) {
                continue;
            }

            if (true == $merchandisingItem->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $merchandisingItem;
        }

        return $results;
    }

    public function totalResultsByFilter(MerchandisingItemFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
