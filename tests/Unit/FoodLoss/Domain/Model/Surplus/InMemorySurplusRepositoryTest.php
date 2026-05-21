<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;

class InMemorySurplusRepositoryTest extends SurplusRepositoryTest
{
    protected function createSUT(): SurplusRepository
    {
        return SurplusRepositoryMother::inMemory();
    }
}
