<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialitySector;

use App\Shared\Domain\Model\Filter;

class MaterialitySectorFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public bool $includeRemoved = false,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
