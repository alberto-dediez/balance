<?php

namespace App\Tests\Unit\Waste\Domain\Model\Waste;

use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleGetter;
use App\Waste\Infrastructure\Repository\Waste\InMemoryWasteRepository;

class WasteRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryWasteRepository
    {
        return new InMemoryWasteRepository(
            localeGetter: new FakeLocaleGetter($locale)
        );
    }
}