<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEconomicSectorRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class EconomicSectorRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryEconomicSectorRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryEconomicSectorRepository();
    }
}
