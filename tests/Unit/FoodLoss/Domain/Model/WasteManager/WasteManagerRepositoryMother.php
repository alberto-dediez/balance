<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\WasteManager;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryWasteManagerRepository;

class WasteManagerRepositoryMother
{
    public static function inMemory(): InMemoryWasteManagerRepository
    {
        return new InMemoryWasteManagerRepository();
    }
}
