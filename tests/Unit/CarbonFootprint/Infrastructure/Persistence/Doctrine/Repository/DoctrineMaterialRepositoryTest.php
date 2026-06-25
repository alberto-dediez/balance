<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrineMaterialRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryTest;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineMaterialRepositoryTest extends MaterialRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): MaterialRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        FakeLocaleSetter::set($locale);

        return new DoctrineMaterialRepository($manager);
    }
}
