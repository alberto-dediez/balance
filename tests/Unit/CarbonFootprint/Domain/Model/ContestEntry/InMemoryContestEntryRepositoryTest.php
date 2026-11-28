<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ContestEntry;

use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRepository;

class InMemoryContestEntryRepositoryTest extends ContestEntryRepositoryTest
{
    protected function createSUT(): ContestEntryRepository
    {
        return ContestEntryRepositoryMother::inMemory();
    }
}
