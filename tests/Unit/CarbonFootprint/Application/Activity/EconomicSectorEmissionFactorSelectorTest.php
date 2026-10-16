<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\EconomicSectorEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use PHPUnit\Framework\TestCase;

class EconomicSectorEmissionFactorSelectorTest extends TestCase
{
    public function testActivityTypeIsEconomicSector(): void
    {
        $sut = new EconomicSectorEmissionFactorSelector(EconomicSectorRepositoryMother::inMemory());

        $this->assertSame(ActivityType::EconomicSector, $sut->activityType());
    }

    public function testSelectReturnsFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::defaultId();

        $entity = EconomicSectorMother::registeredEconomicSector();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId)->withUnit(Unit::Euro));

        $repository = EconomicSectorRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new EconomicSectorEmissionFactorSelector($repository);

        $factor = $sut->select((string) $entity->id(), 2024, null, $groupId);

        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
        $this->assertSame('1.5', $factor->co2eq());
        $this->assertSame(Unit::Euro, $factor->unit());
    }

    public function testSelectIgnoresCondition(): void
    {
        $groupId = EmissionFactorGroupMother::defaultId();

        $entity = EconomicSectorMother::registeredEconomicSector();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId)->withUnit(Unit::Euro));

        $repository = EconomicSectorRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new EconomicSectorEmissionFactorSelector($repository);

        $this->assertTrue(
            $sut->select((string) $entity->id(), 2024, ItemCondition::Recycled, $groupId)
                ->equals($sut->select((string) $entity->id(), 2024, ItemCondition::New, $groupId)),
        );
    }

    public function testSelectThrowsWhenEconomicSectorNotFound(): void
    {
        $sut = new EconomicSectorEmissionFactorSelector(EconomicSectorRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) EconomicSectorId::generate(), 2024, null, EmissionFactorGroupMother::defaultId());
    }

    public function testSelectThrowsWhenNoFactorForRequestedYear(): void
    {
        $entity = EconomicSectorMother::registeredEconomicSector();
        $entity->setEmissionFactor(ActivityMother::emissionFactor(EmissionFactorGroupMother::defaultId())->withUnit(Unit::Euro));

        $repository = EconomicSectorRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new EconomicSectorEmissionFactorSelector($repository);

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) $entity->id(), 2023, null, EmissionFactorGroupMother::defaultId());
    }
}
