<?php

namespace App\Tests\Unit\Waste\Infrastructure\Repository\Waste;

use App\Waste\Domain\Model\Waste\WasteRepository;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryTest;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryMother;

class InMemoryWasteRepositoryTest extends WasteRepositoryTest
{
    public function createSUT(): WasteRepository
    {
        return WasteRepositoryMother::inMemory();
    }
}