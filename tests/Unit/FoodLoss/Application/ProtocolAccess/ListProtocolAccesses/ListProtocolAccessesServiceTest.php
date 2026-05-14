<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\ProtocolAccess\ListProtocolAccesses;

use App\FoodLoss\Application\ProtocolAccess\ListProtocolAccesses\ListProtocolAccessesRequest;
use App\FoodLoss\Application\ProtocolAccess\ListProtocolAccesses\ListProtocolAccessesService;
use App\FoodLoss\Application\ProtocolAccess\ProtocolAccessAuthorizer;
use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessRepositoryMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListProtocolAccessesServiceTest extends TestCase
{
    public function testOwnerSeesAllEmployeesWithLastAccessDate(): void
    {
        $protocolId = (string) ProtocolId::generate();
        $accountId = 'account-test';

        $employeeRepository = EmployeeRepositoryMother::inMemory();
        $employee1 = EmployeeMother::from('Alice', 'alice@test.com', 'emp-1', $accountId);
        $employee2 = EmployeeMother::from('Bob', 'bob@test.com', 'emp-2', $accountId);
        $employee3 = EmployeeMother::from('Carol', 'carol@test.com', 'emp-3', $accountId);
        $employeeRepository->add($employee1);
        $employeeRepository->add($employee2);
        $employeeRepository->add($employee3);

        $accessRepository = ProtocolAccessRepositoryMother::inMemory();
        $accessRepository->add(ProtocolAccessMother::forProtocolAndEmployeeAt($protocolId, 'emp-1', new \DateTimeImmutable('2024-06-01'), $accountId));
        $accessRepository->add(ProtocolAccessMother::forProtocolAndEmployeeAt($protocolId, 'emp-1', new \DateTimeImmutable('2024-01-01'), $accountId));
        $accessRepository->add(ProtocolAccessMother::forProtocolAndEmployeeAt($protocolId, 'emp-2', new \DateTimeImmutable('2024-03-01'), $accountId));

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId($accountId));
        $sut = $this->createSUT($employeeRepository, $accessRepository);

        $response = $sut(
            new ListProtocolAccessesRequest(
                protocolId: new ProtocolId($protocolId),
                accountId: new AccountId($accountId),
            ),
            $user,
        );

        $this->assertCount(3, $response->summaries);

        $this->assertEquals('emp-1', (string) $response->summaries[0]->employee->id());
        $this->assertNotNull($response->summaries[0]->lastAccessedAt);
        $this->assertEquals('2024-06-01', $response->summaries[0]->lastAccessedAt->format('Y-m-d'));

        $this->assertEquals('emp-2', (string) $response->summaries[1]->employee->id());
        $this->assertNotNull($response->summaries[1]->lastAccessedAt);
        $this->assertEquals('2024-03-01', $response->summaries[1]->lastAccessedAt->format('Y-m-d'));

        $this->assertEquals('emp-3', (string) $response->summaries[2]->employee->id());
        $this->assertNull($response->summaries[2]->lastAccessedAt);
    }

    public function testEmployeesWithoutAccessHaveNullLastAccessedAt(): void
    {
        $protocolId = (string) ProtocolId::generate();
        $accountId = 'account-test';

        $employeeRepository = EmployeeRepositoryMother::inMemory();
        $employeeRepository->add(EmployeeMother::from('Alice', 'alice@test.com', 'emp-1', $accountId));

        $accessRepository = ProtocolAccessRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId($accountId));
        $sut = $this->createSUT($employeeRepository, $accessRepository);

        $response = $sut(
            new ListProtocolAccessesRequest(
                protocolId: new ProtocolId($protocolId),
                accountId: new AccountId($accountId),
            ),
            $user,
        );

        $this->assertCount(1, $response->summaries);
        $this->assertNull($response->summaries[0]->lastAccessedAt);
    }

    public function testNonOwnerCannotListProtocolAccesses(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $employeeRepository = EmployeeRepositoryMother::inMemory();
        $accessRepository = ProtocolAccessRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-test'));
        $sut = $this->createSUT($employeeRepository, $accessRepository);

        $sut(
            new ListProtocolAccessesRequest(
                protocolId: ProtocolId::generate(),
                accountId: new AccountId('account-test'),
            ),
            $user,
        );
    }

    private function createSUT(
        \App\HumanResources\Domain\Model\Employee\EmployeeRepository $employeeRepository,
        \App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessRepository $accessRepository,
    ): ListProtocolAccessesService {
        return new ListProtocolAccessesService(
            protocolAccessRepository: $accessRepository,
            employeeRepository: $employeeRepository,
            authorizer: new ProtocolAccessAuthorizer(),
        );
    }
}
