<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;

class EventCarbonFootprintRepositoryMother
{
    public static function inMemory(): InMemoryEventCarbonFootprintRepository
    {
        return new InMemoryEventCarbonFootprintRepository();
    }
}
