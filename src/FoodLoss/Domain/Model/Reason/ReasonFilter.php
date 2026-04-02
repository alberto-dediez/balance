<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Reason;

use App\FoodLoss\Domain\Model\Action\Priority;
use App\Shared\Domain\Model\Filter;

class ReasonFilter
{
    use Filter;

    public function __construct(
        public ?string $description = null,
        public ?Priority $priority = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
