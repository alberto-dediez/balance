<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrineTripCalculationLinkRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineTripCalculationLinkRepositoryTest extends TripCalculationLinkRepositoryTest
{
    protected function createSUT(): TripCalculationLinkRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineTripCalculationLinkRepository($manager);
    }
}
