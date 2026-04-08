<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Incidence;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryIncidenceRepository;

class IncidenceRepositoryMother
{
    public static function inMemory(): InMemoryIncidenceRepository
    {
        return new InMemoryIncidenceRepository();
    }
}
