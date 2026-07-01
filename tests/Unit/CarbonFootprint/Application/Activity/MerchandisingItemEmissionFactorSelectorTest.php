<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\MerchandisingItemEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepositoryMother;
use PHPUnit\Framework\TestCase;

class MerchandisingItemEmissionFactorSelectorTest extends TestCase
{
    public function testSelectReturnsNewFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();

        $entity = MerchandisingItemMother::registeredMerchandisingItem();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId), ActivityMother::emissionFactor($groupId));

        $repository = MerchandisingItemRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new MerchandisingItemEmissionFactorSelector($repository);

        $factor = $sut->select($entity->id(), 2024, ItemCondition::New, $groupId);

        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
    }

    public function testSelectReturnsRecycledFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();

        $entity = MerchandisingItemMother::registeredMerchandisingItem();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId), ActivityMother::emissionFactor($groupId));

        $repository = MerchandisingItemRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new MerchandisingItemEmissionFactorSelector($repository);

        $factor = $sut->select($entity->id(), 2024, ItemCondition::Recycled, $groupId);

        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
    }

    public function testSelectThrowsWhenMerchandisingItemNotFound(): void
    {
        $sut = new MerchandisingItemEmissionFactorSelector(MerchandisingItemRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);
        $sut->select(MerchandisingItemId::generate(), 2024, ItemCondition::New, EmissionFactorGroupMother::default()->id());
    }

    public function testSelectThrowsWhenNoFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();

        $entity = MerchandisingItemMother::registeredMerchandisingItem();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId), ActivityMother::emissionFactor($groupId));

        $repository = MerchandisingItemRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new MerchandisingItemEmissionFactorSelector($repository);

        $this->expectException(\InvalidArgumentException::class);
        $sut->select($entity->id(), 2024, ItemCondition::New, EmissionFactorGroupMother::random()->id());
    }
}
