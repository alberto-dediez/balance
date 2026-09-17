<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem\Unmap;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Shared\Application\CommandBus\Command;

class UnmapBioscoreCatalogItemRequest implements Command
{
    public function __construct(
        public BioscoreCatalogItemId $itemId,
    ) {}
}
