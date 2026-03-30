<?php

declare(strict_types=1);

namespace App\Property\Application\Category\Import;

class ImportCategoriesResponse
{
    public function __construct(
        /** @var CategoryResponse[] $categories */
        public array $categories = [],
        public int $matched = 0,
    ) {}
}
