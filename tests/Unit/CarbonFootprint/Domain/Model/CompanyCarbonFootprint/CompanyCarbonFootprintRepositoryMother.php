<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;

class CompanyCarbonFootprintRepositoryMother
{
    public static function inMemory(): InMemoryCompanyCarbonFootprintRepository
    {
        return new InMemoryCompanyCarbonFootprintRepository();
    }
}
