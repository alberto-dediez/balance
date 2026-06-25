<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Material;

use App\Shared\Domain\Model\Filter;

class MaterialFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?MaterialCategory $category = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
