<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ContestEntry;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryContestEntryRepository;

class ContestEntryRepositoryMother
{
    public static function inMemory(): InMemoryContestEntryRepository
    {
        return new InMemoryContestEntryRepository();
    }
}
