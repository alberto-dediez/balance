<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Category;

use App\Shared\Domain\Model\Filter;

class CategoryFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
