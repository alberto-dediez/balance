<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem\Sync;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Shared\Application\CommandBus\Command;

class SyncBioscoreCatalogsRequest implements Command
{
    public function __construct(
        public ?BioscoreCatalog $catalog = null,
        public \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}
}
