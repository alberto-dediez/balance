<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\OvernightStayEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepositoryMother;
use PHPUnit\Framework\TestCase;

class OvernightStayEmissionFactorSelectorTest extends TestCase
{
    public function testSelectReturnsFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();

        $entity = OvernightStayMother::registeredOvernightStay();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId));

        $repository = OvernightStayRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new OvernightStayEmissionFactorSelector($repository);

        $factor = $sut->select($entity->id(), 2024, $groupId);

        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
    }

    public function testSelectThrowsWhenOvernightStayNotFound(): void
    {
        $sut = new OvernightStayEmissionFactorSelector(OvernightStayRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);
        $sut->select(OvernightStayId::generate(), 2024, EmissionFactorGroupMother::default()->id());
    }

    public function testSelectThrowsWhenNoFactorForRequestedGroup(): void
    {
        $entity = OvernightStayMother::registeredOvernightStay();
        $entity->setEmissionFactor(ActivityMother::emissionFactor(EmissionFactorGroupMother::default()->id()));

        $repository = OvernightStayRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new OvernightStayEmissionFactorSelector($repository);

        $this->expectException(\InvalidArgumentException::class);
        $sut->select($entity->id(), 2024, EmissionFactorGroupMother::random()->id());
    }
}
