<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem\Unmap;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;
use App\Shared\Application\CommandBus\CommandResponse;

class UnmapBioscoreCatalogItemResponse implements CommandResponse
{
    public function __construct(public BioscoreCatalogItem $item) {}
}
