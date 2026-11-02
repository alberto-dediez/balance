<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\SectorBenchmark;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemorySectorBenchmarkRepository;

class SectorBenchmarkRepositoryMother
{
    public static function inMemory(): InMemorySectorBenchmarkRepository
    {
        return new InMemorySectorBenchmarkRepository();
    }
}
