<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Reason\List;

class ListReasonsResponse
{
    /** @param array<\App\FoodLoss\Domain\Model\Reason\Reason> $reasons */
    public function __construct(public array $reasons = [], public int $totalResults = 0) {}
}
