<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\Doctrine\Subscriber;

use App\FoodLoss\Domain\Model\Protocol\ProtocolAccessed;
use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRegistered;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessFilter;
use App\FoodLoss\Infrastructure\Persistence\Doctrine\Subscriber\RegisterProtocolAccessOnProtocolAccessedSubscriber;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessRepositoryMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use PHPUnit\Framework\TestCase;

class RegisterProtocolAccessOnProtocolAccessedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToProtocolAccessed(): void
    {
        $sut = new RegisterProtocolAccessOnProtocolAccessedSubscriber(
            EmployeeRepositoryMother::inMemory(),
            ProtocolAccessRepositoryMother::inMemory(),
        );

        $event = new ProtocolAccessed(
            protocolId: ProtocolId::generate(),
            accountId: AccountId::generate(),
            userId: UserId::generate(),
        );

        $this->assertTrue($sut->isSubscribedTo($event));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = new RegisterProtocolAccessOnProtocolAccessedSubscriber(
            EmployeeRepositoryMother::inMemory(),
            ProtocolAccessRepositoryMother::inMemory(),
        );

        $this->assertFalse($sut->isSubscribedTo(new ProtocolRegistered(
            protocolId: ProtocolId::generate(),
            name: 'test',
            description: 'test',
            path: 'path/test.pdf',
            accountId: AccountId::generate(),
        )));
    }

    public function testHandleCreatesProtocolAccessWhenEmployeeExists(): void
    {
        $userId = UserId::generate();
        $protocolId = ProtocolId::generate();
        $accountId = AccountId::generate();

        $employeeRepository = EmployeeRepositoryMother::inMemory();
        $employee = EmployeeMother::withUserId('emp-1', (string) $userId, (string) $accountId);
        $employeeRepository->add($employee);

        $accessRepository = ProtocolAccessRepositoryMother::inMemory();

        $sut = new RegisterProtocolAccessOnProtocolAccessedSubscriber($employeeRepository, $accessRepository);
        $sut->handle(new ProtocolAccessed(
            protocolId: $protocolId,
            accountId: $accountId,
            userId: $userId,
        ));

        $accesses = $accessRepository->findByFilter(new ProtocolAccessFilter(
            protocolId: $protocolId,
            limit: 0,
        ));

        $this->assertCount(1, $accesses);
        $this->assertNotNull($accesses[0]->protocolId());
        $this->assertNotNull($accesses[0]->employeeId());
        $this->assertTrue($protocolId->equals($accesses[0]->protocolId()));
        $this->assertTrue($employee->id()->equals($accesses[0]->employeeId()));
    }

    public function testHandleIgnoresWhenNoEmployeeLinkedToUser(): void
    {
        $accessRepository = ProtocolAccessRepositoryMother::inMemory();

        $sut = new RegisterProtocolAccessOnProtocolAccessedSubscriber(
            EmployeeRepositoryMother::inMemory(),
            $accessRepository,
        );

        $sut->handle(new ProtocolAccessed(
            protocolId: ProtocolId::generate(),
            accountId: AccountId::generate(),
            userId: UserId::generate(),
        ));

        $accesses = $accessRepository->findByFilter(new ProtocolAccessFilter(limit: 0));
        $this->assertCount(0, $accesses);
    }
}
