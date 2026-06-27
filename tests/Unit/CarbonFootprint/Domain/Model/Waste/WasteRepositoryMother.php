<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Waste;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryWasteRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class WasteRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryWasteRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryWasteRepository();
    }
}
