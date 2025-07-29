<?php

namespace App\Property\Domain\Model\Brand;

use App\Shared\Domain\Model\Filter;

class BrandFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        int $limit = 10
    ) {
        $this->limit = $limit;
    }
}