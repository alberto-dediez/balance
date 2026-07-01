<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\WasteEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteRepositoryMother;
use PHPUnit\Framework\TestCase;

class WasteEmissionFactorSelectorTest extends TestCase
{
    public function testSelectReturnsFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();

        $entity = WasteMother::registeredWaste();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId));

        $repository = WasteRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new WasteEmissionFactorSelector($repository);

        $factor = $sut->select($entity->id(), 2024, $groupId);

        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
    }

    public function testSelectThrowsWhenWasteNotFound(): void
    {
        $sut = new WasteEmissionFactorSelector(WasteRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);
        $sut->select(WasteId::generate(), 2024, EmissionFactorGroupMother::default()->id());
    }

    public function testSelectThrowsWhenNoFactorForRequestedGroup(): void
    {
        $entity = WasteMother::registeredWaste();
        $entity->setEmissionFactor(ActivityMother::emissionFactor(EmissionFactorGroupMother::default()->id()));

        $repository = WasteRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new WasteEmissionFactorSelector($repository);

        $this->expectException(\InvalidArgumentException::class);
        $sut->select($entity->id(), 2024, EmissionFactorGroupMother::random()->id());
    }
}
