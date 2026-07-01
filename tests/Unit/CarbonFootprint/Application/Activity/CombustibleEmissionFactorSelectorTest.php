<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\CombustibleEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use PHPUnit\Framework\TestCase;

class CombustibleEmissionFactorSelectorTest extends TestCase
{
    public function testSelectReturnsFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();

        $entity = CombustibleMother::registeredCombustible();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId));

        $repository = CombustibleRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new CombustibleEmissionFactorSelector($repository);

        $factor = $sut->select($entity->id(), 2024, $groupId);

        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
    }

    public function testSelectThrowsWhenCombustibleNotFound(): void
    {
        $sut = new CombustibleEmissionFactorSelector(CombustibleRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);
        $sut->select(CombustibleId::generate(), 2024, EmissionFactorGroupMother::default()->id());
    }

    public function testSelectThrowsWhenNoFactorForRequestedGroup(): void
    {
        $entity = CombustibleMother::registeredCombustible();
        $entity->setEmissionFactor(ActivityMother::emissionFactor(EmissionFactorGroupMother::default()->id()));

        $repository = CombustibleRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new CombustibleEmissionFactorSelector($repository);

        $this->expectException(\InvalidArgumentException::class);
        $sut->select($entity->id(), 2024, EmissionFactorGroupMother::random()->id());
    }
}
