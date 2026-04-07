<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Packaging;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryPackagingRepository;

class PackagingRepositoryMother
{
    public static function inMemory(): InMemoryPackagingRepository
    {
        return new InMemoryPackagingRepository();
    }
}
