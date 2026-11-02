<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\SectorBenchmark;

use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmarkRepository;

class InMemorySectorBenchmarkRepositoryTest extends SectorBenchmarkRepositoryTest
{
    protected function createSUT(): SectorBenchmarkRepository
    {
        return SectorBenchmarkRepositoryMother::inMemory();
    }
}
