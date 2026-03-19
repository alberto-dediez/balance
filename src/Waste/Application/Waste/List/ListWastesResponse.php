<?php

declare(strict_types=1);

namespace App\Waste\Application\Waste\List;

class ListWastesResponse
{
    public function __construct(public array $wastes = [], public int $totalResults = 0) {}
}
