<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\PurchaseServiceEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepositoryMother;
use PHPUnit\Framework\TestCase;

class PurchaseServiceEmissionFactorSelectorTest extends TestCase
{
    public function testActivityTypeIsPurchaseService(): void
    {
        $sut = new PurchaseServiceEmissionFactorSelector(PurchaseServiceRepositoryMother::inMemory());

        $this->assertSame(ActivityType::PurchaseService, $sut->activityType());
    }

    public function testSelectReturnsFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::defaultId();

        $entity = PurchaseServiceMother::registeredPurchaseService();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId)->withUnit(Unit::Euro));

        $repository = PurchaseServiceRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new PurchaseServiceEmissionFactorSelector($repository);

        $factor = $sut->select((string) $entity->id(), 2024, null, $groupId);

        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
        $this->assertSame('1.5', $factor->co2eq());
        $this->assertSame(Unit::Euro, $factor->unit());
    }

    public function testSelectIgnoresCondition(): void
    {
        $groupId = EmissionFactorGroupMother::defaultId();

        $entity = PurchaseServiceMother::registeredPurchaseService();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId)->withUnit(Unit::Euro));

        $repository = PurchaseServiceRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new PurchaseServiceEmissionFactorSelector($repository);

        $this->assertTrue(
            $sut->select((string) $entity->id(), 2024, ItemCondition::Recycled, $groupId)
                ->equals($sut->select((string) $entity->id(), 2024, ItemCondition::New, $groupId)),
        );
    }

    public function testSelectThrowsWhenPurchaseServiceNotFound(): void
    {
        $sut = new PurchaseServiceEmissionFactorSelector(PurchaseServiceRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) PurchaseServiceId::generate(), 2024, null, EmissionFactorGroupMother::defaultId());
    }

    public function testSelectThrowsWhenNoFactorForRequestedYear(): void
    {
        $entity = PurchaseServiceMother::registeredPurchaseService();
        $entity->setEmissionFactor(ActivityMother::emissionFactor(EmissionFactorGroupMother::defaultId())->withUnit(Unit::Euro));

        $repository = PurchaseServiceRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new PurchaseServiceEmissionFactorSelector($repository);

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) $entity->id(), 2023, null, EmissionFactorGroupMother::defaultId());
    }
}
