<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Waste;

use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryWasteRepositoryTest extends WasteRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): WasteRepository
    {
        return WasteRepositoryMother::inMemory($locale);
    }
}
