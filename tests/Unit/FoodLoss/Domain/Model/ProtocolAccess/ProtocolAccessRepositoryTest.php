<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\ProtocolAccess;

use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessFilter;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ProtocolAccessRepositoryTest extends KernelTestCase
{
    public function testAddAndFind(): void
    {
        $repository = $this->createSUT();

        $access = ProtocolAccessMother::registered();

        $this->assertNull($repository->find($access->id()));

        $repository->add($access);

        $recovered = $repository->find($access->id());

        $this->assertNotNull($recovered);
        $this->assertTrue($access->id()->equals($recovered->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();
        $protocolId = (string) ProtocolId::generate();
        $employeeId = (string) EmployeeId::generate();

        $repository->add(ProtocolAccessMother::forProtocolAndEmployee($protocolId, $employeeId));
        $repository->add(ProtocolAccessMother::forProtocolAndEmployee($protocolId, $employeeId));
        $repository->add(ProtocolAccessMother::random());

        $filter = new ProtocolAccessFilter(protocolId: new ProtocolId($protocolId));
        $results = $repository->findByFilter($filter);

        $this->assertCount(2, $results);
    }

    public function testFindLastAccessPerEmployee(): void
    {
        $repository = $this->createSUT();
        $protocolId = (string) ProtocolId::generate();
        $employee1Id = (string) EmployeeId::generate();
        $employee2Id = (string) EmployeeId::generate();

        $repository->add(ProtocolAccessMother::forProtocolAndEmployeeAt($protocolId, $employee1Id, new \DateTimeImmutable('2024-01-01')));
        $repository->add(ProtocolAccessMother::forProtocolAndEmployeeAt($protocolId, $employee1Id, new \DateTimeImmutable('2024-06-01')));
        $repository->add(ProtocolAccessMother::forProtocolAndEmployeeAt($protocolId, $employee2Id, new \DateTimeImmutable('2024-03-01')));

        $lastAccesses = $repository->findLastAccessPerEmployee(new ProtocolId($protocolId));

        $this->assertCount(2, $lastAccesses);

        $byEmployee = [];
        foreach ($lastAccesses as $access) {
            $byEmployee[(string) $access->employeeId()] = $access;
        }

        $this->assertEquals('2024-06-01', $byEmployee[$employee1Id]->accessedAt()->format('Y-m-d'));
        $this->assertEquals('2024-03-01', $byEmployee[$employee2Id]->accessedAt()->format('Y-m-d'));
    }

    public function testFindHistoryByProtocolAndEmployee(): void
    {
        $repository = $this->createSUT();
        $protocolId = (string) ProtocolId::generate();
        $employeeId = (string) EmployeeId::generate();
        $otherEmployeeId = (string) EmployeeId::generate();

        $repository->add(ProtocolAccessMother::forProtocolAndEmployeeAt($protocolId, $employeeId, new \DateTimeImmutable('2024-01-01')));
        $repository->add(ProtocolAccessMother::forProtocolAndEmployeeAt($protocolId, $employeeId, new \DateTimeImmutable('2024-06-01')));
        $repository->add(ProtocolAccessMother::forProtocolAndEmployee($protocolId, $otherEmployeeId));

        $history = $repository->findHistoryByProtocolAndEmployee(
            new ProtocolId($protocolId),
            new EmployeeId($employeeId),
        );

        $this->assertCount(2, $history);
        $this->assertEquals('2024-06-01', $history[0]->accessedAt()->format('Y-m-d'));
        $this->assertEquals('2024-01-01', $history[1]->accessedAt()->format('Y-m-d'));
    }

    abstract protected function createSUT(): ProtocolAccessRepository;
}
