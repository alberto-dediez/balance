<?php

declare(strict_types=1);

namespace App\Property\Application\Category\List;

class ListCategoriesResponse
{
    public function __construct(public array $categories = [], public int $totalResults = 0) {}
}
