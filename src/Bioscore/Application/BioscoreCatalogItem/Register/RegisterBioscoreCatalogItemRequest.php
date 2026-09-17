<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem\Register;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Shared\Application\CommandBus\Command;

class RegisterBioscoreCatalogItemRequest implements Command
{
    public function __construct(
        public BioscoreCatalog $catalog,
        public int $bioscoreId,
        public string $name,
        public string $unit,
        public \DateTimeImmutable $seenAt = new \DateTimeImmutable('NOW'),
    ) {}
}
