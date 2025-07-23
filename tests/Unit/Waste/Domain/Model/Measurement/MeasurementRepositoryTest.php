<?php

namespace App\Tests\Unit\Waste\Domain\Model\Measurement;

use App\Waste\Domain\Model\Container\ContainerId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Waste\Domain\Model\Measurement\MeasurementFilter;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;

abstract class MeasurementRepositoryTest extends KernelTestCase
{
    public function testAddMeasurementToRepository()
    {   
        $repository = $this->createSUT();

        $measurement = MeasurementMother::random();

        $this->assertNull($repository->find($measurement->id()));

        $repository->add($measurement);

        $recoverMeasurement = $repository->find($measurement->id());

        $this->assertNotNull($recoverMeasurement);
        $this->assertEquals($measurement->id(), $recoverMeasurement->id());
    }

    public function testRemoveMeasurementToRepository()
    {   
        $repository = $this->createSUT();

        $measurement = MeasurementMother::random();

        $repository->add($measurement);

        $recoverMeasurement = $repository->find($measurement->id());

        $this->assertNotNull($recoverMeasurement);
        $this->assertEquals($measurement->id(), $recoverMeasurement->id());

        $repository->remove($measurement);

        $this->assertNull($repository->find($measurement->id()));
    }

    public function testFindByFilter()
    {
        $repository = $this->createSUT();

        $containerId = ContainerId::generate();

        $repository->add(
            MeasurementMother::random()
        );
        $repository->add(
            MeasurementMother::random()
        );
        $repository->add(
            MeasurementMother::random()
        );
        $repository->add(
            MeasurementMother::random()
        );
        $repository->add(
            MeasurementMother::random()
        );
        $repository->add(
            MeasurementMother::fromContainer(
                $containerId
            )
        );

        $filter = new MeasurementFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new MeasurementFilter(
            containerId: $containerId
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements()
    {
        $repository = $this->createSUT();

        $containerId = ContainerId::generate();

        $repository->add(
            MeasurementMother::random()
        );
        $repository->add(
            MeasurementMother::random()
        );
        $repository->add(
            MeasurementMother::random()
        );
        $repository->add(
            MeasurementMother::random()
        );
        $repository->add(
            MeasurementMother::random()
        );
        $repository->add(
            MeasurementMother::fromContainer(
                $containerId
            )
        );

        $filter = new MeasurementFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new MeasurementFilter(
            limit: 0
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    abstract protected function createSUT(): MeasurementRepository;
}
