<?php

namespace App\Tests\Unit\Waste\Domain\Model\DailyWaste;

use App\Waste\Infrastructure\Repository\DailyWaste\InMemoryDailyWasteRepository;

class DailyWasteRepositoryMother
{
    public static function inMemory(): InMemoryDailyWasteRepository
    {
        return new InMemoryDailyWasteRepository();
    }
}