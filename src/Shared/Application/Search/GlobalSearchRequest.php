<?php

declare(strict_types=1);

namespace App\Shared\Application\Search;

class GlobalSearchRequest
{
    public function __construct(
        public string $term,
        public int $limitPerCategory = 5,
    ) {}
}
