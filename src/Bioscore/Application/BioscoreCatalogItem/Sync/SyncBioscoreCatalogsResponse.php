<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem\Sync;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;
use App\Shared\Application\CommandBus\CommandResponse;

class SyncBioscoreCatalogsResponse implements CommandResponse
{
    /**
     * @param list<BioscoreCatalogItem> $newPending
     * @param list<BioscoreCatalogItem> $invalidated
     * @param list<BioscoreCatalog>     $failedCatalogs
     */
    public function __construct(
        public int $registered = 0,
        public int $updated = 0,
        public int $autoMapped = 0,
        public int $withdrawn = 0,
        public int $restored = 0,
        public array $newPending = [],
        public array $invalidated = [],
        public array $failedCatalogs = [],
    ) {}

    public function hasNews(): bool
    {
        return [] !== $this->newPending || [] !== $this->invalidated;
    }
}
