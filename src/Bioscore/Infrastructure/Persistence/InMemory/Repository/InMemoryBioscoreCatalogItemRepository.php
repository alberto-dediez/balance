<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\Persistence\InMemory\Repository;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemFilter;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepository;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreMappingStatus;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryBioscoreCatalogItemRepository implements BioscoreCatalogItemRepository
{
    /** @param array<string, BioscoreCatalogItem> $items */
    public function __construct(
        private array $items = [],
    ) {}

    public function add(BioscoreCatalogItem $item): void
    {
        $this->items[(string) $item->id()] = $item;
    }

    public function find(BioscoreCatalogItemId $id): ?BioscoreCatalogItem
    {
        return $this->items[(string) $id] ?? null;
    }

    public function findByCatalogAndBioscoreId(BioscoreCatalog $catalog, int $bioscoreId): ?BioscoreCatalogItem
    {
        foreach ($this->items as $item) {
            if ($item->catalog() === $catalog && $item->bioscoreId() === $bioscoreId) {
                return $item;
            }
        }

        return null;
    }

    /** @return array<int, BioscoreCatalogItem> */
    public function findByFilter(BioscoreCatalogItemFilter $filter): array
    {
        $matching = [];

        foreach ($this->items as $item) {
            if (!$this->matches($item, $filter)) {
                continue;
            }

            $matching[] = $item;
        }

        $this->sort($matching, $filter);

        $offset = $filter->page * $filter->limit;

        if ($filter->limit > 0) {
            return array_slice($matching, $offset, $filter->limit);
        }

        return $matching;
    }

    public function totalResultsByFilter(BioscoreCatalogItemFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }

    public function countPending(BioscoreCatalog $catalog): int
    {
        return $this->totalResultsByFilter(new BioscoreCatalogItemFilter(
            catalog: $catalog,
            status: BioscoreMappingStatus::Unmapped,
            withdrawn: false,
        ));
    }

    private function matches(BioscoreCatalogItem $item, BioscoreCatalogItemFilter $filter): bool
    {
        if (null !== $filter->catalog && $item->catalog() !== $filter->catalog) {
            return false;
        }

        if (null !== $filter->status && $item->status() !== $filter->status) {
            return false;
        }

        if (null !== $filter->withdrawn && $item->isWithdrawn() !== $filter->withdrawn) {
            return false;
        }

        if (null !== $filter->name && '' !== $filter->name && !str_contains(mb_strtolower($item->name()), mb_strtolower($filter->name))) {
            return false;
        }

        if (null !== $filter->targetId && '' !== $filter->targetId && $item->targetId() !== $filter->targetId) {
            return false;
        }

        return true;
    }

    /** @param list<BioscoreCatalogItem> $items */
    private function sort(array &$items, BioscoreCatalogItemFilter $filter): void
    {
        $way = 'ASC' === $filter->orderWay ? 1 : -1;

        usort($items, static function (BioscoreCatalogItem $a, BioscoreCatalogItem $b) use ($filter, $way): int {
            $comparison = match ($filter->orderBy) {
                'status' => strcmp($a->status()->value, $b->status()->value),
                'lastSeenAt' => $a->lastSeenAt() <=> $b->lastSeenAt(),
                'firstSeenAt' => $a->firstSeenAt() <=> $b->firstSeenAt(),
                default => strcmp($a->name(), $b->name()),
            };

            return 0 !== $comparison ? $comparison * $way : strcmp((string) $a->id(), (string) $b->id());
        });
    }
}
