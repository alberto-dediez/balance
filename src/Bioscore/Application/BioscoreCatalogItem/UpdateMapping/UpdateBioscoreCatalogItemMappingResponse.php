<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem\UpdateMapping;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateBioscoreCatalogItemMappingResponse implements CommandResponse
{
    public function __construct(public BioscoreCatalogItem $item) {}
}
