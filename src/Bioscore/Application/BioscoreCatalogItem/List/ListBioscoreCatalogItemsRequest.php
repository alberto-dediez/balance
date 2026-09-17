<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem\List;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemFilter;
use App\Shared\Application\CommandBus\Command;

class ListBioscoreCatalogItemsRequest extends BioscoreCatalogItemFilter implements Command {}
