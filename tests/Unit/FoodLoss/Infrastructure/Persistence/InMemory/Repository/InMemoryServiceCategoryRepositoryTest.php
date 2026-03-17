<?php

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepositoryTest;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;

class InMemoryServiceCategoryRepositoryTest extends ServiceCategoryRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): ServiceCategoryRepository
    {
        return ServiceCategoryRepositoryMother::inMemory($locale);
    }
}
