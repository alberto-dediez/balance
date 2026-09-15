<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryElectricityMixRepository;

class ElectricityMixRepositoryMother
{
    public static function inMemory(): InMemoryElectricityMixRepository
    {
        return new InMemoryElectricityMixRepository();
    }
}
