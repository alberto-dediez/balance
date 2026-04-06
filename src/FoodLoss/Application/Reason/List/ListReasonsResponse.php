<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Reason\List;

use App\FoodLoss\Domain\Model\Reason\Reason;

class ListReasonsResponse
{
    /** @param array<Reason> $reasons */
    public function __construct(public array $reasons = [], public int $totalResults = 0) {}
}
