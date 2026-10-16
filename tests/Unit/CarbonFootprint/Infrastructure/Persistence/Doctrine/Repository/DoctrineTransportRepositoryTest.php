<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrineTransportRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryTest;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineTransportRepositoryTest extends TransportRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): TransportRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        FakeLocaleSetter::set($locale);

        return new DoctrineTransportRepository($manager);
    }
}
