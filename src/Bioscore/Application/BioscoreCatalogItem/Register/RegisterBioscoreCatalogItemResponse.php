<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem\Register;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterBioscoreCatalogItemResponse implements CommandResponse
{
    public function __construct(public BioscoreCatalogItem $item) {}
}
