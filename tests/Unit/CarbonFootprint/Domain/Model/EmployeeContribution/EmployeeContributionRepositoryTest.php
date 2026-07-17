<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeContribution;

use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use PHPUnit\Framework\TestCase;

abstract class EmployeeContributionRepositoryTest extends TestCase
{
    public function testAddEmployeeContributionToRepository(): void
    {
        $repository = $this->createSUT();

        $employeeContribution = EmployeeContributionMother::registeredEmployeeContribution();

        $this->assertNull($repository->find($employeeContribution->id()));

        $repository->add($employeeContribution);

        $recovered = $repository->find($employeeContribution->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($employeeContribution->id(), $recovered->id());
    }

    public function testFindByToken(): void
    {
        $repository = $this->createSUT();

        $employeeContribution = EmployeeContributionMother::registeredEmployeeContribution();

        $repository->add($employeeContribution);

        $recovered = $repository->findByToken($employeeContribution->token());

        $this->assertNotNull($recovered);
        $this->assertEquals($employeeContribution->id(), $recovered->id());
    }

    public function testFindByTokenReturnsNullWhenNotFound(): void
    {
        $repository = $this->createSUT();

        $this->assertNull($repository->findByToken('invalid-token'));
    }

    public function testFindByEventCarbonFootprintId(): void
    {
        $repository = $this->createSUT();

        $eventCarbonFootprintId = (string) EventCarbonFootprintId::generate();

        $repository->add(EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount($eventCarbonFootprintId, 'account-id'));
        $repository->add(EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount($eventCarbonFootprintId, 'account-id'));
        $repository->add(EmployeeContributionMother::registeredEmployeeContribution());

        $recovered = $repository->findByEventCarbonFootprintId(new EventCarbonFootprintId($eventCarbonFootprintId));

        $this->assertCount(2, $recovered);
    }

    public function testFindByEventCarbonFootprintIdAndEmployeeId(): void
    {
        $repository = $this->createSUT();

        $eventCarbonFootprintId = (string) EventCarbonFootprintId::generate();
        $employeeId = (string) EmployeeId::generate();

        $employeeContribution = EmployeeContributionMother::fromEventCarbonFootprintIdAndEmployeeId($eventCarbonFootprintId, $employeeId);
        $repository->add($employeeContribution);
        $repository->add(EmployeeContributionMother::fromEventCarbonFootprintIdAndEmployeeId($eventCarbonFootprintId, (string) EmployeeId::generate()));

        $recovered = $repository->findByEventCarbonFootprintIdAndEmployeeId(
            new EventCarbonFootprintId($eventCarbonFootprintId),
            new EmployeeId($employeeId),
        );

        $this->assertNotNull($recovered);
        $this->assertEquals($employeeContribution->id(), $recovered->id());
    }

    public function testFindByEventCarbonFootprintIdAndEmployeeIdReturnsNullWhenNotFound(): void
    {
        $repository = $this->createSUT();

        $this->assertNull($repository->findByEventCarbonFootprintIdAndEmployeeId(
            EventCarbonFootprintId::generate(),
            EmployeeId::generate(),
        ));
    }

    abstract protected function createSUT(): EmployeeContributionRepository;
}
