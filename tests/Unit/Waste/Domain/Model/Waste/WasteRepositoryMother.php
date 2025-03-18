<?php

namespace App\Tests\Unit\Waste\Domain\Model\Waste;

use App\Waste\Infrastructure\Repository\Waste\InMemoryWasteRepository;

class WasteRepositoryMother
{
    public static function inMemory(): InMemoryWasteRepository
    {
        return new InMemoryWasteRepository();
    }
}