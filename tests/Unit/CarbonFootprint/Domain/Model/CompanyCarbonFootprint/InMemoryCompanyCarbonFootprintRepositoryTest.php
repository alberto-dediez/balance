<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;

class InMemoryCompanyCarbonFootprintRepositoryTest extends CompanyCarbonFootprintRepositoryTest
{
    protected function createSUT(): CompanyCarbonFootprintRepository
    {
        return CompanyCarbonFootprintRepositoryMother::inMemory();
    }
}
