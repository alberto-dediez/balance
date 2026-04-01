<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;

class InMemoryCriticalPointRepositoryTest extends CriticalPointRepositoryTest
{
    protected function createSUT(): CriticalPointRepository
    {
        return CriticalPointRepositoryMother::inMemory();
    }
}
