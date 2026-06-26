<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMachineryFuelRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class MachineryFuelRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryMachineryFuelRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryMachineryFuelRepository();
    }
}
