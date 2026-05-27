<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\WasteManager\List;

class ListWasteManagersResponse
{
    /** @param array<\App\FoodLoss\Domain\Model\WasteManager\WasteManager> $wasteManagers */
    public function __construct(public array $wasteManagers = [], public int $totalResults = 0) {}
}
