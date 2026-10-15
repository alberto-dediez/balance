<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\UseProfileEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepositoryMother;
use PHPUnit\Framework\TestCase;

class UseProfileEmissionFactorSelectorTest extends TestCase
{
    public function testActivityTypeIsUseProfile(): void
    {
        $sut = new UseProfileEmissionFactorSelector(UseProfileRepositoryMother::inMemory());

        $this->assertSame(ActivityType::UseProfile, $sut->activityType());
    }

    public function testSelectReturnsFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::defaultId();

        $entity = UseProfileMother::registeredUseProfile();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId)->withUnit(Unit::Piece));

        $repository = UseProfileRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new UseProfileEmissionFactorSelector($repository);

        $factor = $sut->select((string) $entity->id(), 2024, null, $groupId);

        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
        $this->assertSame('1.5', $factor->co2eq());
        $this->assertSame(Unit::Piece, $factor->unit());
    }

    public function testSelectIgnoresCondition(): void
    {
        $groupId = EmissionFactorGroupMother::defaultId();

        $entity = UseProfileMother::registeredUseProfile();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId)->withUnit(Unit::Piece));

        $repository = UseProfileRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new UseProfileEmissionFactorSelector($repository);

        $this->assertTrue(
            $sut->select((string) $entity->id(), 2024, ItemCondition::Recycled, $groupId)
                ->equals($sut->select((string) $entity->id(), 2024, ItemCondition::New, $groupId)),
        );
    }

    public function testSelectThrowsWhenUseProfileNotFound(): void
    {
        $sut = new UseProfileEmissionFactorSelector(UseProfileRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) UseProfileId::generate(), 2024, null, EmissionFactorGroupMother::defaultId());
    }

    public function testSelectThrowsWhenNoFactorForRequestedYear(): void
    {
        $entity = UseProfileMother::registeredUseProfile();
        $entity->setEmissionFactor(ActivityMother::emissionFactor(EmissionFactorGroupMother::defaultId())->withUnit(Unit::Piece));

        $repository = UseProfileRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new UseProfileEmissionFactorSelector($repository);

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) $entity->id(), 2023, null, EmissionFactorGroupMother::defaultId());
    }
}
