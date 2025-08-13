<?php

namespace App\Tests\Unit\Waste\Infrastructure\Repository\DailyWaste;

use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteRepositoryTest;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteRepositoryMother;

class InMemoryDailyWasteRepositoryTest extends DailyWasteRepositoryTest
{
    public function createSUT(): DailyWasteRepository
    {
        return DailyWasteRepositoryMother::inMemory();
    }
}