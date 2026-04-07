<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Protocol\List;

class ListProtocolsResponse
{
    /** @param array<\App\FoodLoss\Domain\Model\Protocol\Protocol> $protocols */
    public function __construct(public array $protocols = [], public int $totalResults = 0) {}
}
