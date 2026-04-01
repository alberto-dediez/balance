<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryCriticalPointRepository;

class CriticalPointRepositoryMother
{
    public static function inMemory(): InMemoryCriticalPointRepository
    {
        return new InMemoryCriticalPointRepository();
    }
}
