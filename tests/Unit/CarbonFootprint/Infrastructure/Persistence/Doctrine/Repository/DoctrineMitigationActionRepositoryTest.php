<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRepository;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrineMitigationActionRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRepositoryTest;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineMitigationActionRepositoryTest extends MitigationActionRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): MitigationActionRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        FakeLocaleSetter::set($locale);

        return new DoctrineMitigationActionRepository($manager);
    }
}
