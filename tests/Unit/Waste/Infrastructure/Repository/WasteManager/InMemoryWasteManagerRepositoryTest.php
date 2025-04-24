<?php

namespace App\Tests\Unit\Waste\Infrastructure\Repository\WasteManager;

use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;
use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerRepositoryTest;
use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerRepositoryMother;

class InMemoryWasteManagerRepositoryTest extends WasteManagerRepositoryTest
{
    public function createSUT(): WasteManagerRepository
    {
        return WasteManagerRepositoryMother::inMemory();
    }
}