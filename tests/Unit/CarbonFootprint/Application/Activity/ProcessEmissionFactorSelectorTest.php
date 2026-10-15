<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\ProcessEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessRepositoryMother;
use PHPUnit\Framework\TestCase;

class ProcessEmissionFactorSelectorTest extends TestCase
{
    public function testActivityTypeIsProcess(): void
    {
        $sut = new ProcessEmissionFactorSelector(ProcessRepositoryMother::inMemory());

        $this->assertSame(ActivityType::Process, $sut->activityType());
    }

    public function testSelectReturnsFactorForRequestedGroup(): void
    {
        $groupId = EmissionFactorGroupMother::defaultId();

        $entity = ProcessMother::registeredProcess();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId)->withUnit(Unit::Piece));

        $repository = ProcessRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new ProcessEmissionFactorSelector($repository);

        $factor = $sut->select((string) $entity->id(), 2024, null, $groupId);

        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
        $this->assertSame('1.5', $factor->co2eq());
        $this->assertSame(Unit::Piece, $factor->unit());
    }

    public function testSelectIgnoresCondition(): void
    {
        $groupId = EmissionFactorGroupMother::defaultId();

        $entity = ProcessMother::registeredProcess();
        $entity->setEmissionFactor(ActivityMother::emissionFactor($groupId)->withUnit(Unit::Piece));

        $repository = ProcessRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new ProcessEmissionFactorSelector($repository);

        $this->assertTrue(
            $sut->select((string) $entity->id(), 2024, ItemCondition::Recycled, $groupId)
                ->equals($sut->select((string) $entity->id(), 2024, ItemCondition::New, $groupId)),
        );
    }

    public function testSelectThrowsWhenProcessNotFound(): void
    {
        $sut = new ProcessEmissionFactorSelector(ProcessRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) ProcessId::generate(), 2024, null, EmissionFactorGroupMother::defaultId());
    }

    public function testSelectThrowsWhenNoFactorForRequestedYear(): void
    {
        $entity = ProcessMother::registeredProcess();
        $entity->setEmissionFactor(ActivityMother::emissionFactor(EmissionFactorGroupMother::defaultId())->withUnit(Unit::Piece));

        $repository = ProcessRepositoryMother::inMemory();
        $repository->add($entity);

        $sut = new ProcessEmissionFactorSelector($repository);

        $this->expectException(\InvalidArgumentException::class);
        $sut->select((string) $entity->id(), 2023, null, EmissionFactorGroupMother::defaultId());
    }
}
