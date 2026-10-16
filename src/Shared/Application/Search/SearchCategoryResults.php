<?php

declare(strict_types=1);

namespace App\Shared\Application\Search;

final readonly class SearchCategoryResults
{
    /** @param list<SearchResult> $results */
    public function __construct(
        public SearchCategory $category,
        public array $results,
    ) {}
}
