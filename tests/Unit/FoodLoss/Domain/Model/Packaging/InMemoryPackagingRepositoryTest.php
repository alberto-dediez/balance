<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Packaging;

use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;

class InMemoryPackagingRepositoryTest extends PackagingRepositoryTest
{
    protected function createSUT(): PackagingRepository
    {
        return PackagingRepositoryMother::inMemory();
    }
}
