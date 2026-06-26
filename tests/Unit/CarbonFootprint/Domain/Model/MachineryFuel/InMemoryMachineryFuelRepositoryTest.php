<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel;

use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryMachineryFuelRepositoryTest extends MachineryFuelRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): MachineryFuelRepository
    {
        return MachineryFuelRepositoryMother::inMemory($locale);
    }
}
