<?php

namespace App\Tests\Unit\Waste\Infrastructure\Persistence\InMemory\Repository;

use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerRepositoryTest;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;

class InMemoryWasteManagerRepositoryTest extends WasteManagerRepositoryTest
{
    public function createSUT(): WasteManagerRepository
    {
        return WasteManagerRepositoryMother::inMemory();
    }
}
