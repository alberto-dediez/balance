<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence\List;

use App\FoodLoss\Domain\Model\Incidence\Incidence;

class ListIncidencesResponse
{
    /** @param array<Incidence> $incidences */
    public function __construct(public array $incidences = [], public int $totalResults = 0) {}
}
