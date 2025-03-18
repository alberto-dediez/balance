<?php

namespace App\Tests\Unit\Waste\Infrastructure\Repository\Measurement;

use Doctrine\ORM\EntityManagerInterface;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryTest;
use App\Waste\Infrastructure\Repository\Measurement\DoctrineMeasurementRepository;

class DoctrineMeasurementRepositoryTest extends MeasurementRepositoryTest
{
    public function createSUT(): MeasurementRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineMeasurementRepository($manager);
    }
}