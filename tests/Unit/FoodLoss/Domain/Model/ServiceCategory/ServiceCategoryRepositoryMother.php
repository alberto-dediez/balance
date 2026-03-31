<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryServiceCategoryRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class ServiceCategoryRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryServiceCategoryRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryServiceCategoryRepository();
    }
}
