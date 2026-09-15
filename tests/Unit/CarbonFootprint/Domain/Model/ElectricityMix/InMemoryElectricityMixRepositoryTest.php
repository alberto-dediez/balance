<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;

class InMemoryElectricityMixRepositoryTest extends ElectricityMixRepositoryTest
{
    protected function createSUT(): ElectricityMixRepository
    {
        return ElectricityMixRepositoryMother::inMemory();
    }
}
