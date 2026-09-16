<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;

class InMemoryPropertyCarbonFootprintRepositoryTest extends PropertyCarbonFootprintRepositoryTest
{
    protected function createSUT(): PropertyCarbonFootprintRepository
    {
        return PropertyCarbonFootprintRepositoryMother::inMemory();
    }
}
