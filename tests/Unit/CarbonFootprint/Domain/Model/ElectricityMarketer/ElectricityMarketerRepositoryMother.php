<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryElectricityMarketerRepository;

class ElectricityMarketerRepositoryMother
{
    public static function inMemory(): InMemoryElectricityMarketerRepository
    {
        return new InMemoryElectricityMarketerRepository();
    }
}
