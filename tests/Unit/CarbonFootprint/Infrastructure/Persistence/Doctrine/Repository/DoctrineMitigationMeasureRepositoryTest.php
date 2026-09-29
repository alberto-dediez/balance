<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRepository;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrineMitigationMeasureRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRepositoryTest;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineMitigationMeasureRepositoryTest extends MitigationMeasureRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): MitigationMeasureRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        FakeLocaleSetter::set($locale);

        return new DoctrineMitigationMeasureRepository($manager);
    }
}
