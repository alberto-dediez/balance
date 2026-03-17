<?php

namespace App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory;

use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleGetter;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryServiceCategoryRepository;

class ServiceCategoryRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryServiceCategoryRepository
    {
        return new InMemoryServiceCategoryRepository(
            localeGetter: new FakeLocaleGetter($locale)
        );
    }
}
