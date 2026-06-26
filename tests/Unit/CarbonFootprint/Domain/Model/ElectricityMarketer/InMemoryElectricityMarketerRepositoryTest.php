<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;

class InMemoryElectricityMarketerRepositoryTest extends ElectricityMarketerRepositoryTest
{
    protected function createSUT(): ElectricityMarketerRepository
    {
        return ElectricityMarketerRepositoryMother::inMemory();
    }
}
