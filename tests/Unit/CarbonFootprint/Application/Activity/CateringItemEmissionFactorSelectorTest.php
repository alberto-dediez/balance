<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\CateringItemEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use PHPUnit\Framework\TestCase;

class CateringItemEmissionFactorSelectorTest extends TestCase
{
    public function testSelectReturnsNewFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();

        $entity = CateringItemMother::registeredCateringItem();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId), ActivityMother::emissionFactor($groupId));

        $repository = CateringItemRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new CateringItemEmissionFactorSelector($repository);

        $factor = $sut->select((string) $entity->id(), 2024, ItemCondition::New, $groupId);

        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
    }

    public function testSelectReturnsRecycledFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();

        $entity = CateringItemMother::registeredCateringItem();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId), ActivityMother::emissionFactor($groupId));

        $repository = CateringItemRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new CateringItemEmissionFactorSelector($repository);

        $factor = $sut->select((string) $entity->id(), 2024, ItemCondition::Recycled, $groupId);

        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
    }

    public function testSelectThrowsWhenCateringItemNotFound(): void
    {
        $sut = new CateringItemEmissionFactorSelector(CateringItemRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) CateringItemId::generate(), 2024, ItemCondition::New, EmissionFactorGroupMother::default()->id());
    }

    public function testSelectThrowsWhenNoFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();

        $entity = CateringItemMother::registeredCateringItem();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId), ActivityMother::emissionFactor($groupId));

        $repository = CateringItemRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new CateringItemEmissionFactorSelector($repository);

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) $entity->id(), 2024, ItemCondition::New, EmissionFactorGroupMother::random()->id());
    }
}
