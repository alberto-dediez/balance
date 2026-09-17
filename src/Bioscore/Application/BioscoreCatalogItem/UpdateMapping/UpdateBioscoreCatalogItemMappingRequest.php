<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem\UpdateMapping;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Shared\Application\CommandBus\Command;

class UpdateBioscoreCatalogItemMappingRequest implements Command
{
    public function __construct(
        public BioscoreCatalogItemId $itemId,
        public ?string $targetId = null,
        public bool $ignore = false,
    ) {}
}
