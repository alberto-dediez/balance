<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\Measurement;

use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteMother;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Measurement\MeasurementFilter;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class MeasurementRepositoryTest extends KernelTestCase
{
    public function testAddMeasurementToRepository(): void
    {
        $repository = $this->createSUT();

        $measurement = MeasurementMother::random();

        $this->assertNull($repository->find($measurement->id()));

        $repository->add($measurement);

        $recoverMeasurement = $repository->find($measurement->id());

        $this->assertNotNull($recoverMeasurement);
        $this->assertEquals($measurement->id(), $recoverMeasurement->id());
    }

    public function testRemoveMeasurementToRepository(): void
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

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $containerId = ContainerId::generate();

        $repository->add(
            MeasurementMother::random(),
        );
        $repository->add(
            MeasurementMother::random(),
        );
        $repository->add(
            MeasurementMother::random(),
        );
        $repository->add(
            MeasurementMother::random(),
        );
        $repository->add(
            MeasurementMother::random(),
        );
        $repository->add(
            MeasurementMother::fromContainer(
                $containerId,
            ),
        );

        $filter = new MeasurementFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new MeasurementFilter(
            containerId: $containerId,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByDay(): void
    {
        $dailyWaste = DailyWasteMother::random();

        $repository = $this->createSUT();

        ContainerId::generate();

        $repository->add(
            MeasurementMother::fromDay($dailyWaste),
        );
        $repository->add(
            MeasurementMother::fromDay($dailyWaste),
        );
        $repository->add(
            MeasurementMother::fromDay($dailyWaste),
        );
        $repository->add(
            MeasurementMother::fromDay($dailyWaste),
        );
        $repository->add(
            MeasurementMother::random(),
        );
        $repository->add(
            MeasurementMother::random(),
        );

        self::assertNotEmpty($dailyWaste->propertyId());
        self::assertNotEmpty($dailyWaste->accountId());
        self::assertNotEmpty($dailyWaste->wasteId());
        self::assertNotEmpty($dailyWaste->wasteManagerId());

        $result = $repository->findByDay(
            day: $dailyWaste->day(),
            propertyId: $dailyWaste->propertyId(),
            wasteId: $dailyWaste->wasteId(),
            purpose: $dailyWaste->purpose(),
            wasteManagerId: $dailyWaste->wasteManagerId(),
            accountId: $dailyWaste->accountId(),
        );

        $this->assertCount(4, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $containerId = ContainerId::generate();

        $repository->add(
            MeasurementMother::random(),
        );
        $repository->add(
            MeasurementMother::random(),
        );
        $repository->add(
            MeasurementMother::random(),
        );
        $repository->add(
            MeasurementMother::random(),
        );
        $repository->add(
            MeasurementMother::random(),
        );
        $repository->add(
            MeasurementMother::fromContainer(
                $containerId,
            ),
        );

        $filter = new MeasurementFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new MeasurementFilter(
            limit: 0,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    abstract protected function createSUT(): MeasurementRepository;
}
