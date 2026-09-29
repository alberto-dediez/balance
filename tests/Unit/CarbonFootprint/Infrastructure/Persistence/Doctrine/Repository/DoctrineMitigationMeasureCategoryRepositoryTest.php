<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepository;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrineMitigationMeasureCategoryRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepositoryTest;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineMitigationMeasureCategoryRepositoryTest extends MitigationMeasureCategoryRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): MitigationMeasureCategoryRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        FakeLocaleSetter::set($locale);

        return new DoctrineMitigationMeasureCategoryRepository($manager);
    }
}
