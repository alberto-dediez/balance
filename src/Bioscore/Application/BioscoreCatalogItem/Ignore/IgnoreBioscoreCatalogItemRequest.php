<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem\Ignore;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Shared\Application\CommandBus\Command;

class IgnoreBioscoreCatalogItemRequest implements Command
{
    public function __construct(
        public BioscoreCatalogItemId $itemId,
    ) {}
}
