<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\RefrigerantGasEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use PHPUnit\Framework\TestCase;

class RefrigerantGasEmissionFactorSelectorTest extends TestCase
{
    public function testSelectReturnsFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();

        $entity = RefrigerantGasMother::registeredRefrigerantGas();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId));

        $repository = RefrigerantGasRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new RefrigerantGasEmissionFactorSelector($repository);

        $factor = $sut->select((string) $entity->id(), 2024, null, $groupId);

        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
    }

    public function testSelectThrowsWhenRefrigerantGasNotFound(): void
    {
        $sut = new RefrigerantGasEmissionFactorSelector(RefrigerantGasRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) RefrigerantGasId::generate(), 2024, null, EmissionFactorGroupMother::default()->id());
    }

    public function testSelectThrowsWhenNoFactorForRequestedGroup(): void
    {
        $entity = RefrigerantGasMother::registeredRefrigerantGas();
        $entity->setEmissionFactor(ActivityMother::emissionFactor(EmissionFactorGroupMother::default()->id()));

        $repository = RefrigerantGasRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new RefrigerantGasEmissionFactorSelector($repository);

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) $entity->id(), 2024, null, EmissionFactorGroupMother::random()->id());
    }
}
