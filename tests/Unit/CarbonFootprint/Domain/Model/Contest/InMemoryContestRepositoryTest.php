<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Contest;

use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;

class InMemoryContestRepositoryTest extends ContestRepositoryTest
{
    protected function createSUT(): ContestRepository
    {
        return ContestRepositoryMother::inMemory();
    }
}
