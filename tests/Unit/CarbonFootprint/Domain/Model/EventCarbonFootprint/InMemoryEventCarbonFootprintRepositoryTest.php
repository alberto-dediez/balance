<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;

class InMemoryEventCarbonFootprintRepositoryTest extends EventCarbonFootprintRepositoryTest
{
    protected function createSUT(): EventCarbonFootprintRepository
    {
        return EventCarbonFootprintRepositoryMother::inMemory();
    }
}
