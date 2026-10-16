<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem;

use App\CarbonFootprint\Domain\Model\Transport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;

final readonly class CatalogTarget
{
    /** @param list<string> $names */
    public function __construct(
        public string $id,
        public string $name,
        public array $names,
        public Unit $unit,
        public ?Vehicle $vehicle = null,
    ) {}
}
