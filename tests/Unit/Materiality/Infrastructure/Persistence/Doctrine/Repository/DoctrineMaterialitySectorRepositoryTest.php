<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Infrastructure\Persistence\Doctrine\Repository;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use App\Materiality\Infrastructure\Persistence\Doctrine\Repository\DoctrineMaterialitySectorRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepositoryTest;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineMaterialitySectorRepositoryTest extends MaterialitySectorRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): MaterialitySectorRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        FakeLocaleSetter::set($locale);

        return new DoctrineMaterialitySectorRepository($manager);
    }
}
