<?php

declare(strict_types=1);

namespace App\Property\Application\Brand\Import;

class ImportBrandsResponse
{
    public function __construct(
        /** @var BrandResponse[] $brands */
        public array $brands = [],
        public int $matched = 0,
    ) {}
}
