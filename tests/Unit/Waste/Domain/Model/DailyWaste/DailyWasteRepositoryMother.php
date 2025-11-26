<?php

namespace App\Tests\Unit\Waste\Domain\Model\DailyWaste;

use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryDailyWasteRepository;

class DailyWasteRepositoryMother
{
    public static function inMemory(): InMemoryDailyWasteRepository
    {
        return new InMemoryDailyWasteRepository();
    }
}
