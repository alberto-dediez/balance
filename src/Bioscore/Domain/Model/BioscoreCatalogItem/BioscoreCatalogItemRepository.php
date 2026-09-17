<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreCatalogItem;

interface BioscoreCatalogItemRepository
{
    public function add(BioscoreCatalogItem $item): void;

    public function find(BioscoreCatalogItemId $id): ?BioscoreCatalogItem;

    public function findByCatalogAndBioscoreId(BioscoreCatalog $catalog, int $bioscoreId): ?BioscoreCatalogItem;

    /** @return array<BioscoreCatalogItem> */
    public function findByFilter(BioscoreCatalogItemFilter $filter): array;

    public function totalResultsByFilter(BioscoreCatalogItemFilter $filter): int;

    public function countPending(BioscoreCatalog $catalog): int;
}
