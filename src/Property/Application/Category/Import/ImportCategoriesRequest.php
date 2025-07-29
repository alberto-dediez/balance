<?php

namespace App\Property\Application\Category\Import;

class ImportCategoriesRequest
{
    public function __construct(
        /** @var CategoryRequest[] $categories */
        public array $categories,
    ) {

    }
}