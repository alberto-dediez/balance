<?php

namespace App\Property\Application\Brand\Import;

class ImportBrandsRequest
{
    public function __construct(
        /** @var BrandRequest[] $brands */
        public array $brands,
    ) {

    }
}