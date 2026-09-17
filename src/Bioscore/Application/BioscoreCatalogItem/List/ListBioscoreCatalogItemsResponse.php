<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem\List;

use App\Bioscore\Application\BioscoreCatalogItem\CatalogTarget;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;

class ListBioscoreCatalogItemsResponse
{
    /**
     * @param array<BioscoreCatalogItem>   $items
     * @param array<string, CatalogTarget> $targetsById
     */
    public function __construct(
        public array $items = [],
        public int $totalResults = 0,
        public int $pendingCount = 0,
        public array $targetsById = [],
    ) {}
}
