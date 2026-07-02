<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\MaterialEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
use PHPUnit\Framework\TestCase;

class MaterialEmissionFactorSelectorTest extends TestCase
{
    public function testSelectReturnsNewFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();

        $entity = MaterialMother::registeredMaterial();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId), ActivityMother::emissionFactor($groupId));

        $repository = MaterialRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new MaterialEmissionFactorSelector($repository);

        $factor = $sut->select((string) $entity->id(), 2024, ItemCondition::New, $groupId);

        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
    }

    public function testSelectReturnsRecycledFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();

        $entity = MaterialMother::registeredMaterial();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId), ActivityMother::emissionFactor($groupId));

        $repository = MaterialRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new MaterialEmissionFactorSelector($repository);

        $factor = $sut->select((string) $entity->id(), 2024, ItemCondition::Recycled, $groupId);

        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
    }

    public function testSelectThrowsWhenMaterialNotFound(): void
    {
        $sut = new MaterialEmissionFactorSelector(MaterialRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) MaterialId::generate(), 2024, ItemCondition::New, EmissionFactorGroupMother::default()->id());
    }

    public function testSelectThrowsWhenNoFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();

        $entity = MaterialMother::registeredMaterial();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId), ActivityMother::emissionFactor($groupId));

        $repository = MaterialRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new MaterialEmissionFactorSelector($repository);

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) $entity->id(), 2024, ItemCondition::New, EmissionFactorGroupMother::random()->id());
    }
}
