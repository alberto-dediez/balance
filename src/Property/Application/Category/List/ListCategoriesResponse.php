<?php

declare(strict_types=1);

namespace App\Property\Application\Category\List;

class ListCategoriesResponse
{
    /**
     * @param \App\Property\Domain\Model\Category\Category[] $categories
     */
    public function __construct(public array $categories = [], public int $totalResults = 0) {}
}
