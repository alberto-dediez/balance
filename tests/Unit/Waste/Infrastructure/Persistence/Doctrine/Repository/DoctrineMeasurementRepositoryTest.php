<?php

namespace App\Tests\Unit\Waste\Infrastructure\Persistence\Doctrine\Repository;

use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryTest;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Waste\Infrastructure\Persistence\Doctrine\Repository\DoctrineMeasurementRepository;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineMeasurementRepositoryTest extends MeasurementRepositoryTest
{
    public function createSUT(): MeasurementRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineMeasurementRepository($manager);
    }
}
