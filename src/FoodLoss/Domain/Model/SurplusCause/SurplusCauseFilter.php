<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\SurplusCause;

use App\Shared\Domain\Model\Filter;

class SurplusCauseFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
