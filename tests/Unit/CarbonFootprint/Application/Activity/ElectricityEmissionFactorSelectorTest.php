<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\ElectricityEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use PHPUnit\Framework\TestCase;

class ElectricityEmissionFactorSelectorTest extends TestCase
{
    public function testSelectReturnsMarketerFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();

        $marketer = ElectricityMarketerMother::registeredElectricityMarketer();
        $marketer->setEmissionFactor(ActivityMother::emissionFactor($groupId));
        $marketerRepository = ElectricityMarketerRepositoryMother::inMemory();
        $marketerRepository->add($marketer);

        $gdOFactor = GdOFactorMother::registeredGdOFactor();
        $gdOFactorRepository = GdOFactorRepositoryMother::inMemory();
        $gdOFactorRepository->add($gdOFactor);

        $sut = new ElectricityEmissionFactorSelector($marketerRepository, $gdOFactorRepository);

        $factor = $sut->select($marketer->id(), $gdOFactor->id(), 2024, $groupId);

        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
    }

    public function testSelectThrowsWhenElectricityMarketerNotFound(): void
    {
        $gdOFactor = GdOFactorMother::registeredGdOFactor();
        $gdOFactorRepository = GdOFactorRepositoryMother::inMemory();
        $gdOFactorRepository->add($gdOFactor);

        $sut = new ElectricityEmissionFactorSelector(ElectricityMarketerRepositoryMother::inMemory(), $gdOFactorRepository);

        $this->expectException(\InvalidArgumentException::class);
        $sut->select(ElectricityMarketerId::generate(), $gdOFactor->id(), 2024, EmissionFactorGroupMother::default()->id());
    }

    public function testSelectThrowsWhenGdOFactorNotFound(): void
    {
        $marketer = ElectricityMarketerMother::registeredElectricityMarketer();
        $marketerRepository = ElectricityMarketerRepositoryMother::inMemory();
        $marketerRepository->add($marketer);

        $sut = new ElectricityEmissionFactorSelector($marketerRepository, GdOFactorRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);
        $sut->select($marketer->id(), GdOFactorId::generate(), 2024, EmissionFactorGroupMother::default()->id());
    }

    public function testSelectThrowsWhenNoFactorForRequestedGroup(): void
    {
        $marketer = ElectricityMarketerMother::registeredElectricityMarketer();
        $marketer->setEmissionFactor(ActivityMother::emissionFactor(EmissionFactorGroupMother::default()->id()));
        $marketerRepository = ElectricityMarketerRepositoryMother::inMemory();
        $marketerRepository->add($marketer);

        $gdOFactor = GdOFactorMother::registeredGdOFactor();
        $gdOFactorRepository = GdOFactorRepositoryMother::inMemory();
        $gdOFactorRepository->add($gdOFactor);

        $sut = new ElectricityEmissionFactorSelector($marketerRepository, $gdOFactorRepository);

        $this->expectException(\InvalidArgumentException::class);
        $sut->select($marketer->id(), $gdOFactor->id(), 2024, EmissionFactorGroupMother::random()->id());
    }
}
