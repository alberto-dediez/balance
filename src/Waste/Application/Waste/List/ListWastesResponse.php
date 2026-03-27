<?php

declare(strict_types=1);

namespace App\Waste\Application\Waste\List;

use App\Waste\Domain\Model\Waste\Waste;

class ListWastesResponse
{
    /** @param array<int, Waste> $wastes */
    public function __construct(public array $wastes = [], public int $totalResults = 0) {}
}
