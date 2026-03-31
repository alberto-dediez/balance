<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepositoryTest;

class InMemoryServiceCategoryRepositoryTest extends ServiceCategoryRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): ServiceCategoryRepository
    {
        return ServiceCategoryRepositoryMother::inMemory($locale);
    }
}
