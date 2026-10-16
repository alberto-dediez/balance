<?php

declare(strict_types=1);

namespace App\Shared\Application\Search;

class GlobalSearchResponse
{
    /** @param list<SearchCategoryResults> $categories */
    public function __construct(
        public SearchTerm $term,
        public array $categories,
        public bool $searched,
    ) {}
}
