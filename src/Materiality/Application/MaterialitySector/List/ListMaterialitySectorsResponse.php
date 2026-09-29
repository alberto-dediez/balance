<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialitySector\List;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;

class ListMaterialitySectorsResponse
{
    /** @param array<MaterialitySector> $materialitySectors */
    public function __construct(
        public array $materialitySectors = [],
        public int $totalResults = 0,
    ) {}
}
