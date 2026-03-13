<?php

namespace App\Tests\Unit\FoodLoss\Domain\Model\Action;

use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleGetter;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryActionRepository;

class ActionRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryActionRepository
    {
        return new InMemoryActionRepository(
            localeGetter: new FakeLocaleGetter($locale)
        );
    }
}
