<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\List;

class ListSurplusesResponse
{
    /** @param array<\App\FoodLoss\Domain\Model\Surplus\Surplus> $surpluses */
    public function __construct(public array $surpluses = [], public int $totalResults = 0) {}
}
