<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreCatalogItem;

final readonly class BioscoreCatalogEntry
{
    public function __construct(
        public int $bioscoreId,
        public string $name,
        public string $unit,
    ) {}
}
