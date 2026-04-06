<?php

declare(strict_types=1);

namespace App\Property\Application\Category\List;

use App\Property\Domain\Model\Category\Category;

class ListCategoriesResponse
{
    /**
     * @param Category[] $categories
     */
    public function __construct(public array $categories = [], public int $totalResults = 0) {}
}
