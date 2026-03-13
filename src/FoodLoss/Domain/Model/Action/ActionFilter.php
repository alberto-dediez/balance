<?php

namespace App\FoodLoss\Domain\Model\Action;

use App\Shared\Domain\Model\Filter;

class ActionFilter
{
    use Filter;

    public function __construct(
        public ?string $description = null,
        public ?Priority $priority = null,
        int $limit = 10
    ) {
        $this->limit = $limit;
    }
}