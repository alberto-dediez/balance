<?php

declare(strict_types=1);

namespace App\Property\Application\Category\Import;

class ImportCategoriesRequest
{
    public function __construct(
        /** @var CategoryRequest[] $categories */
        public array $categories,
    ) {}
}
