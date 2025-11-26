<?php

namespace App\Tests\Unit\Waste\Domain\Model\WasteManager;

use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryWasteManagerRepository;

class WasteManagerRepositoryMother
{
    public static function inMemory(): InMemoryWasteManagerRepository
    {
        return new InMemoryWasteManagerRepository();
    }
}
