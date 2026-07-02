<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\RoadTransportEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepositoryMother;
use PHPUnit\Framework\TestCase;

class RoadTransportEmissionFactorSelectorTest extends TestCase
{
    public function testSelectReturnsFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();

        $entity = RoadTransportMother::registeredRoadTransport();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId));

        $repository = RoadTransportRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new RoadTransportEmissionFactorSelector($repository);

        $factor = $sut->select((string) $entity->id(), 2024, null, $groupId);

        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
    }

    public function testSelectThrowsWhenRoadTransportNotFound(): void
    {
        $sut = new RoadTransportEmissionFactorSelector(RoadTransportRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) RoadTransportId::generate(), 2024, null, EmissionFactorGroupMother::default()->id());
    }

    public function testSelectThrowsWhenNoFactorForRequestedGroup(): void
    {
        $entity = RoadTransportMother::registeredRoadTransport();
        $entity->setEmissionFactor(ActivityMother::emissionFactor(EmissionFactorGroupMother::default()->id()));

        $repository = RoadTransportRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new RoadTransportEmissionFactorSelector($repository);

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) $entity->id(), 2024, null, EmissionFactorGroupMother::random()->id());
    }
}
