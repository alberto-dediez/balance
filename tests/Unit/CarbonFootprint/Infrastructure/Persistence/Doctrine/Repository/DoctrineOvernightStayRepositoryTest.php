<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrineOvernightStayRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepositoryTest;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineOvernightStayRepositoryTest extends OvernightStayRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): OvernightStayRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        FakeLocaleSetter::set($locale);

        return new DoctrineOvernightStayRepository($manager);
    }
}
