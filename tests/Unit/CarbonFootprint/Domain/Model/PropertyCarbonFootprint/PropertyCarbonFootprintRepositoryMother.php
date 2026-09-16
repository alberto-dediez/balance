<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;

class PropertyCarbonFootprintRepositoryMother
{
    public static function inMemory(): InMemoryPropertyCarbonFootprintRepository
    {
        return new InMemoryPropertyCarbonFootprintRepository();
    }
}
