<?php

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleGetter;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepositoryTest;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository\DoctrineServiceCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineServiceCategoryRepositoryTest extends ServiceCategoryRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): ServiceCategoryRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineServiceCategoryRepository($manager, new FakeLocaleGetter($locale));
    }
}
