<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector;

use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryEconomicSectorRepositoryTest extends EconomicSectorRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): EconomicSectorRepository
    {
        return EconomicSectorRepositoryMother::inMemory($locale);
    }
}
