<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerFilter;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ElectricityMarketerRepositoryTest extends KernelTestCase
{
    public function testAddElectricityMarketerToRepository(): void
    {
        $repository = $this->createSUT();

        $electricityMarketer = ElectricityMarketerMother::registeredElectricityMarketer();

        $this->assertNull($repository->find($electricityMarketer->id()));

        $repository->add($electricityMarketer);

        $recoveredElectricityMarketer = $repository->find($electricityMarketer->id());

        $this->assertNotNull($recoveredElectricityMarketer);
        $this->assertEquals($electricityMarketer->id(), $recoveredElectricityMarketer->id());
    }

    public function testRemoveElectricityMarketerToRepository(): void
    {
        $repository = $this->createSUT();

        $electricityMarketer = ElectricityMarketerMother::registeredElectricityMarketer();

        $repository->add($electricityMarketer);

        $recoveredElectricityMarketer = $repository->find($electricityMarketer->id());

        $this->assertNotNull($recoveredElectricityMarketer);
        $this->assertEquals($electricityMarketer->id(), $recoveredElectricityMarketer->id());

        $repository->remove($electricityMarketer);

        $this->assertNull($repository->find($electricityMarketer->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(ElectricityMarketerMother::random());
        $repository->add(ElectricityMarketerMother::random());
        $repository->add(ElectricityMarketerMother::random());
        $repository->add(ElectricityMarketerMother::random());
        $repository->add(ElectricityMarketerMother::random());
        $repository->add(ElectricityMarketerMother::fromName('Nombre específico'));

        $filter = new ElectricityMarketerFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new ElectricityMarketerFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(ElectricityMarketerMother::random());
        $repository->add(ElectricityMarketerMother::random());
        $repository->add(ElectricityMarketerMother::random());
        $repository->add(ElectricityMarketerMother::random());
        $repository->add(ElectricityMarketerMother::random());
        $repository->add(ElectricityMarketerMother::fromName('Nombre específico'));

        $filter = new ElectricityMarketerFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $electricityMarketer = ElectricityMarketerMother::registeredElectricityMarketer();

        $repository->add($electricityMarketer);

        $recoveredElectricityMarketer = $repository->find($electricityMarketer->id());

        $this->assertNotNull($recoveredElectricityMarketer);

        $recoveredElectricityMarketer->remove();

        $repository->add($electricityMarketer);

        $this->assertNull($repository->find($electricityMarketer->id()));
    }

    abstract protected function createSUT(): ElectricityMarketerRepository;
}
