<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\MachineryFuelEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepositoryMother;
use PHPUnit\Framework\TestCase;

class MachineryFuelEmissionFactorSelectorTest extends TestCase
{
    public function testSelectReturnsFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();

        $entity = MachineryFuelMother::registeredMachineryFuel();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId));

        $repository = MachineryFuelRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new MachineryFuelEmissionFactorSelector($repository);

        $factor = $sut->select((string) $entity->id(), 2024, null, $groupId);

        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
    }

    public function testSelectThrowsWhenMachineryFuelNotFound(): void
    {
        $sut = new MachineryFuelEmissionFactorSelector(MachineryFuelRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) MachineryFuelId::generate(), 2024, null, EmissionFactorGroupMother::default()->id());
    }

    public function testSelectThrowsWhenNoFactorForRequestedGroup(): void
    {
        $entity = MachineryFuelMother::registeredMachineryFuel();
        $entity->setEmissionFactor(ActivityMother::emissionFactor(EmissionFactorGroupMother::default()->id()));

        $repository = MachineryFuelRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new MachineryFuelEmissionFactorSelector($repository);

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) $entity->id(), 2024, null, EmissionFactorGroupMother::random()->id());
    }
}
