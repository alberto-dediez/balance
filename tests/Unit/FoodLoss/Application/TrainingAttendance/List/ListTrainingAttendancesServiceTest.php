<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\TrainingAttendance\List;

use App\FoodLoss\Application\TrainingAttendance\List\ListTrainingAttendancesRequest;
use App\FoodLoss\Application\TrainingAttendance\List\ListTrainingAttendancesService;
use App\FoodLoss\Application\TrainingAttendance\TrainingAttendanceAuthorizer;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepositoryMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListTrainingAttendancesServiceTest extends TestCase
{
    public function testOwnerSeesAllEmployeesSummaries(): void
    {
        $accountId = 'account-test';
        $sessionId = (string) TrainingSessionId::generate();

        $employeeRepository = EmployeeRepositoryMother::inMemory();
        $employeeRepository->add(EmployeeMother::from('Alice', 'alice@test.com', 'emp-1', $accountId));
        $employeeRepository->add(EmployeeMother::from('Bob', 'bob@test.com', 'emp-2', $accountId));
        $employeeRepository->add(EmployeeMother::from('Carol', 'carol@test.com', 'emp-3', $accountId));

        $attendanceRepository = TrainingAttendanceRepositoryMother::inMemory();
        $attendanceRepository->add(TrainingAttendanceMother::forSessionAndEmployee($sessionId, 'emp-1', $accountId));
        $attendanceRepository->add(TrainingAttendanceMother::forSessionAndEmployee($sessionId, 'emp-1', $accountId));
        $attendanceRepository->add(TrainingAttendanceMother::forSessionAndEmployee($sessionId, 'emp-2', $accountId));

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId($accountId));
        $sut = $this->createSUT($employeeRepository, $attendanceRepository);

        $response = $sut(
            new ListTrainingAttendancesRequest(
                trainingSessionId: new TrainingSessionId($sessionId),
                accountId: new AccountId($accountId),
            ),
            $user,
        );

        $this->assertCount(3, $response->summaries);

        $summaryByEmployee = [];
        foreach ($response->summaries as $summary) {
            $summaryByEmployee[(string) $summary->employee->id()] = $summary;
        }

        $this->assertTrue($summaryByEmployee['emp-1']->hasAttended());
        $this->assertCount(2, $summaryByEmployee['emp-1']->attendances);

        $this->assertTrue($summaryByEmployee['emp-2']->hasAttended());
        $this->assertCount(1, $summaryByEmployee['emp-2']->attendances);

        $this->assertFalse($summaryByEmployee['emp-3']->hasAttended());
        $this->assertCount(0, $summaryByEmployee['emp-3']->attendances);
    }

    public function testAttendedEmployeesAppearBeforeNonAttended(): void
    {
        $accountId = 'account-test';
        $sessionId = (string) TrainingSessionId::generate();

        $employeeRepository = EmployeeRepositoryMother::inMemory();
        $employeeRepository->add(EmployeeMother::from('Alice', 'alice@test.com', 'emp-1', $accountId));
        $employeeRepository->add(EmployeeMother::from('Bob', 'bob@test.com', 'emp-2', $accountId));

        $attendanceRepository = TrainingAttendanceRepositoryMother::inMemory();
        $attendanceRepository->add(TrainingAttendanceMother::forSessionAndEmployee($sessionId, 'emp-2', $accountId));

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId($accountId));
        $sut = $this->createSUT($employeeRepository, $attendanceRepository);

        $response = $sut(
            new ListTrainingAttendancesRequest(
                trainingSessionId: new TrainingSessionId($sessionId),
                accountId: new AccountId($accountId),
            ),
            $user,
        );

        $this->assertTrue($response->summaries[0]->hasAttended());
        $this->assertFalse($response->summaries[1]->hasAttended());
    }

    public function testNonOwnerCannotListAttendances(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-test'));
        $sut = $this->createSUT(EmployeeRepositoryMother::inMemory(), TrainingAttendanceRepositoryMother::inMemory());

        $sut(
            new ListTrainingAttendancesRequest(
                trainingSessionId: TrainingSessionId::generate(),
                accountId: new AccountId('account-test'),
            ),
            $user,
        );
    }

    private function createSUT(
        \App\HumanResources\Domain\Model\Employee\EmployeeRepository $employeeRepository,
        \App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository $attendanceRepository,
    ): ListTrainingAttendancesService {
        return new ListTrainingAttendancesService(
            trainingAttendanceRepository: $attendanceRepository,
            employeeRepository: $employeeRepository,
            authorizer: new TrainingAttendanceAuthorizer(
                EmployeeRepositoryMother::inMemory(),
            ),
        );
    }
}
