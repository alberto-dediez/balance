<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem\Map;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Shared\Application\CommandBus\Command;

class MapBioscoreCatalogItemRequest implements Command
{
    public function __construct(
        public BioscoreCatalogItemId $itemId,
        public string $targetId,
    ) {}
}
