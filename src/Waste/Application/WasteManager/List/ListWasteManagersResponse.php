<?php

declare(strict_types=1);

namespace App\Waste\Application\WasteManager\List;

use App\Waste\Domain\Model\WasteManager\WasteManager;

class ListWasteManagersResponse
{
    /** @param array<int, WasteManager> $wasteManagers */
    public function __construct(public array $wasteManagers = [], public int $totalResults = 0) {}
}
