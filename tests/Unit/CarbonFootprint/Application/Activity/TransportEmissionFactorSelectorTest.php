<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\TransportEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;
use PHPUnit\Framework\TestCase;

class TransportEmissionFactorSelectorTest extends TestCase
{
    public function testSelectReturnsFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();

        $entity = TransportMother::registeredTransport();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId));

        $repository = TransportRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new TransportEmissionFactorSelector($repository);

        $factor = $sut->select((string) $entity->id(), 2024, null, $groupId);

        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
    }

    public function testSelectThrowsWhenTransportNotFound(): void
    {
        $sut = new TransportEmissionFactorSelector(TransportRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) TransportId::generate(), 2024, null, EmissionFactorGroupMother::default()->id());
    }

    public function testSelectThrowsWhenNoFactorForRequestedGroup(): void
    {
        $entity = TransportMother::registeredTransport();
        $entity->setEmissionFactor(ActivityMother::emissionFactor(EmissionFactorGroupMother::default()->id()));

        $repository = TransportRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new TransportEmissionFactorSelector($repository);

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) $entity->id(), 2024, null, EmissionFactorGroupMother::random()->id());
    }
}
