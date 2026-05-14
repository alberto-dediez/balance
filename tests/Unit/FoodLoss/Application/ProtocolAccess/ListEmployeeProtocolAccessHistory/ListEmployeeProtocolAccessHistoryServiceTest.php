<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\ProtocolAccess\ListEmployeeProtocolAccessHistory;

use App\FoodLoss\Application\ProtocolAccess\ListEmployeeProtocolAccessHistory\ListEmployeeProtocolAccessHistoryRequest;
use App\FoodLoss\Application\ProtocolAccess\ListEmployeeProtocolAccessHistory\ListEmployeeProtocolAccessHistoryService;
use App\FoodLoss\Application\ProtocolAccess\ProtocolAccessAuthorizer;
use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessRepositoryMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListEmployeeProtocolAccessHistoryServiceTest extends TestCase
{
    public function testOwnerCanViewEmployeeHistory(): void
    {
        $protocolId = (string) ProtocolId::generate();
        $accountId = 'account-test';

        $employeeRepository = EmployeeRepositoryMother::inMemory();
        $employee = EmployeeMother::from('Alice', 'alice@test.com', 'emp-1', $accountId);
        $employeeRepository->add($employee);

        $accessRepository = ProtocolAccessRepositoryMother::inMemory();
        $accessRepository->add(ProtocolAccessMother::forProtocolAndEmployeeAt($protocolId, 'emp-1', new \DateTimeImmutable('2024-06-01'), $accountId));
        $accessRepository->add(ProtocolAccessMother::forProtocolAndEmployeeAt($protocolId, 'emp-1', new \DateTimeImmutable('2024-01-01'), $accountId));
        $accessRepository->add(ProtocolAccessMother::forProtocolAndEmployee($protocolId, (string) EmployeeId::generate(), $accountId));

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId($accountId));
        $sut = $this->createSUT($employeeRepository, $accessRepository);

        $response = $sut(
            new ListEmployeeProtocolAccessHistoryRequest(
                protocolId: new ProtocolId($protocolId),
                employeeId: new EmployeeId('emp-1'),
                accountId: new AccountId($accountId),
            ),
            $user,
        );

        $this->assertEquals('emp-1', (string) $response->employee->id());
        $this->assertCount(2, $response->accesses);
        $this->assertEquals('2024-06-01', $response->accesses[0]->accessedAt()->format('Y-m-d'));
        $this->assertEquals('2024-01-01', $response->accesses[1]->accessedAt()->format('Y-m-d'));
    }

    public function testHistoryIsEmptyWhenNoAccesses(): void
    {
        $accountId = 'account-test';

        $employeeRepository = EmployeeRepositoryMother::inMemory();
        $employee = EmployeeMother::from('Alice', 'alice@test.com', 'emp-1', $accountId);
        $employeeRepository->add($employee);

        $accessRepository = ProtocolAccessRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId($accountId));
        $sut = $this->createSUT($employeeRepository, $accessRepository);

        $response = $sut(
            new ListEmployeeProtocolAccessHistoryRequest(
                protocolId: ProtocolId::generate(),
                employeeId: new EmployeeId('emp-1'),
                accountId: new AccountId($accountId),
            ),
            $user,
        );

        $this->assertEquals('emp-1', (string) $response->employee->id());
        $this->assertCount(0, $response->accesses);
    }

    public function testEmployeeNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $accountId = 'account-test';
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId($accountId));
        $sut = $this->createSUT(EmployeeRepositoryMother::inMemory(), ProtocolAccessRepositoryMother::inMemory());

        $sut(
            new ListEmployeeProtocolAccessHistoryRequest(
                protocolId: ProtocolId::generate(),
                employeeId: EmployeeId::generate(),
                accountId: new AccountId($accountId),
            ),
            $user,
        );
    }

    public function testNonOwnerCannotViewHistory(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $accountId = 'account-test';
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId($accountId));
        $sut = $this->createSUT(EmployeeRepositoryMother::inMemory(), ProtocolAccessRepositoryMother::inMemory());

        $sut(
            new ListEmployeeProtocolAccessHistoryRequest(
                protocolId: ProtocolId::generate(),
                employeeId: EmployeeId::generate(),
                accountId: new AccountId($accountId),
            ),
            $user,
        );
    }

    private function createSUT(
        \App\HumanResources\Domain\Model\Employee\EmployeeRepository $employeeRepository,
        \App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessRepository $accessRepository,
    ): ListEmployeeProtocolAccessHistoryService {
        return new ListEmployeeProtocolAccessHistoryService(
            protocolAccessRepository: $accessRepository,
            employeeRepository: $employeeRepository,
            authorizer: new ProtocolAccessAuthorizer(),
        );
    }
}
