<?php

namespace App\Tests\Unit\Waste\Infrastructure\Repository\Waste;

use App\Waste\Domain\Model\Waste\WasteRepository;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryTest;
use App\Waste\Infrastructure\Repository\Waste\InMemoryWasteRepository;

class InMemoryWasteRepositoryTest extends WasteRepositoryTest
{
    public function createSUT(): WasteRepository
    {
        return new InMemoryWasteRepository();
    }
}