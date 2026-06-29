<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrineGdOFactorRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryTest;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineGdOFactorRepositoryTest extends GdOFactorRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): GdOFactorRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        FakeLocaleSetter::set($locale);

        return new DoctrineGdOFactorRepository($manager);
    }
}
