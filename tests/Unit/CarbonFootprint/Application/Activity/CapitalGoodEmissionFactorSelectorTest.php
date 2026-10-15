<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\CapitalGoodEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use PHPUnit\Framework\TestCase;

class CapitalGoodEmissionFactorSelectorTest extends TestCase
{
    public function testActivityTypeIsCapitalGood(): void
    {
        $sut = new CapitalGoodEmissionFactorSelector(CapitalGoodRepositoryMother::inMemory());

        $this->assertSame(ActivityType::CapitalGood, $sut->activityType());
    }

    public function testSelectReturnsFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::defaultId();

        $entity = CapitalGoodMother::registeredCapitalGood();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId)->withUnit(Unit::Euro));

        $repository = CapitalGoodRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new CapitalGoodEmissionFactorSelector($repository);

        $factor = $sut->select((string) $entity->id(), 2024, null, $groupId);

        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
        $this->assertSame('1.5', $factor->co2eq());
        $this->assertSame(Unit::Euro, $factor->unit());
    }

    public function testSelectIgnoresCondition(): void
    {
        $groupId = EmissionFactorGroupMother::defaultId();

        $entity = CapitalGoodMother::registeredCapitalGood();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId)->withUnit(Unit::Euro));

        $repository = CapitalGoodRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new CapitalGoodEmissionFactorSelector($repository);

        $this->assertTrue(
            $sut->select((string) $entity->id(), 2024, ItemCondition::Recycled, $groupId)
                ->equals($sut->select((string) $entity->id(), 2024, ItemCondition::New, $groupId)),
        );
    }

    public function testSelectThrowsWhenCapitalGoodNotFound(): void
    {
        $sut = new CapitalGoodEmissionFactorSelector(CapitalGoodRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) CapitalGoodId::generate(), 2024, null, EmissionFactorGroupMother::defaultId());
    }

    public function testSelectThrowsWhenNoFactorForRequestedYear(): void
    {
        $entity = CapitalGoodMother::registeredCapitalGood();
        $entity->setEmissionFactor(ActivityMother::emissionFactor(EmissionFactorGroupMother::defaultId())->withUnit(Unit::Euro));

        $repository = CapitalGoodRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new CapitalGoodEmissionFactorSelector($repository);

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) $entity->id(), 2023, null, EmissionFactorGroupMother::defaultId());
    }
}
