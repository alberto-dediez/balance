<?php

declare(strict_types=1);

namespace App\Property\Application\Brand\Import;

class ImportBrandsRequest
{
    public function __construct(
        /** @var BrandRequest[] $brands */
        public array $brands,
    ) {}
}
