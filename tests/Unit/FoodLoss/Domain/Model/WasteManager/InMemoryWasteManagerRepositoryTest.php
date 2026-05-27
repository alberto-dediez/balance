<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\WasteManager;

use App\FoodLoss\Domain\Model\WasteManager\WasteManagerRepository;

class InMemoryWasteManagerRepositoryTest extends WasteManagerRepositoryTest
{
    protected function createSUT(): WasteManagerRepository
    {
        return WasteManagerRepositoryMother::inMemory();
    }
}
