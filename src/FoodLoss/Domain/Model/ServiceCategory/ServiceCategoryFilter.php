<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\ServiceCategory;

use App\Shared\Domain\Model\Filter;

class ServiceCategoryFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
