<?php

namespace App\Tests\Unit\Waste\Infrastructure\Persistence\InMemory\Repository;

use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteRepositoryTest;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;

class InMemoryDailyWasteRepositoryTest extends DailyWasteRepositoryTest
{
    public function createSUT(): DailyWasteRepository
    {
        return DailyWasteRepositoryMother::inMemory();
    }
}
