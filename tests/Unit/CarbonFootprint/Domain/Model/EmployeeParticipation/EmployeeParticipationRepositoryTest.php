<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeParticipation;

use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use PHPUnit\Framework\TestCase;

abstract class EmployeeParticipationRepositoryTest extends TestCase
{
    public function testAddEmployeeParticipationToRepository(): void
    {
        $repository = $this->createSUT();

        $employeeParticipation = EmployeeParticipationMother::registeredEmployeeParticipation();

        $this->assertNull($repository->find($employeeParticipation->id()));

        $repository->add($employeeParticipation);

        $recovered = $repository->find($employeeParticipation->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($employeeParticipation->id(), $recovered->id());
    }

    public function testFindByEventCarbonFootprintId(): void
    {
        $repository = $this->createSUT();

        $eventCarbonFootprintId = (string) EventCarbonFootprintId::generate();
        $employeeParticipation = EmployeeParticipationMother::fromEventCarbonFootprintId($eventCarbonFootprintId);

        $repository->add($employeeParticipation);

        $recovered = $repository->findByEventCarbonFootprintId(new EventCarbonFootprintId($eventCarbonFootprintId));

        $this->assertNotNull($recovered);
        $this->assertEquals($employeeParticipation->id(), $recovered->id());
    }

    public function testFindByEventCarbonFootprintIdReturnsNullWhenNotFound(): void
    {
        $repository = $this->createSUT();

        $this->assertNull($repository->findByEventCarbonFootprintId(EventCarbonFootprintId::generate()));
    }

    abstract protected function createSUT(): EmployeeParticipationRepository;
}
