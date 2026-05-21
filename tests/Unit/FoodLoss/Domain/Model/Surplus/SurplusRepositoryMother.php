<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemorySurplusRepository;

class SurplusRepositoryMother
{
    public static function inMemory(): InMemorySurplusRepository
    {
        return new InMemorySurplusRepository();
    }
}
