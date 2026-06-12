<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\TrainingSession\Dashboard;

use App\FoodLoss\Application\Protocol\ProtocolAuthorizer;
use App\FoodLoss\Application\TrainingSession\Dashboard\GetTrainingDashboardRequest;
use App\FoodLoss\Application\TrainingSession\Dashboard\GetTrainingDashboardService;
use App\FoodLoss\Application\TrainingSession\TrainingSessionAuthorizer;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessRepository;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\Protocol\ProtocolMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Protocol\ProtocolRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetTrainingDashboardServiceTest extends TestCase
{
    private const ACCOUNT_ID = 'account-id';
    private const SESSION_ONE_ID = '00000000-0000-0000-0000-000000000001';
    private const SESSION_TWO_ID = '00000000-0000-0000-0000-000000000002';
    private const PROTOCOL_ONE_ID = '00000000-0000-0000-0000-000000000011';
    private const PROTOCOL_TWO_ID = '00000000-0000-0000-0000-000000000012';

    public function testUseCase(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $sut = $this->createSUT();

        $request = new GetTrainingDashboardRequest(accountId: $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $response = $sut($request, $user);

        $this->assertSame(2, $response->totalTrainingSessions);
        $this->assertSame(3, $response->totalTrainingAttendances);
        $this->assertSame(1.5, $response->averageAttendancesPerSession());
        $this->assertSame(2, $response->totalProtocols);
        $this->assertSame(1, $response->readProtocols);
        $this->assertSame(50.0, $response->readProtocolsPercentage());
        $this->assertSame(2, $response->totalProtocolAccesses);

        $this->assertSame(['Manipulación de alimentos', 'Higiene'], $response->attendancesBySession->labels);
        $this->assertSame([2, 1], $response->attendancesBySession->values);

        $this->assertSame(['02/2026', '03/2026', '04/2026'], $response->sessionsByMonth->labels);
        $this->assertSame([1, 0, 1], $response->sessionsByMonth->values);

        $this->assertSame(['Protocolo A'], $response->accessesByProtocol->labels);
        $this->assertSame([2], $response->accessesByProtocol->values);

        $this->assertSame(['02/2026', '03/2026', '04/2026'], $response->accessesByMonth->labels);
        $this->assertSame([1, 0, 1], $response->accessesByMonth->values);
    }

    public function testFiltersByDateRange(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $sut = $this->createSUT();

        $request = new GetTrainingDashboardRequest(accountId: $accountId);
        $request->dateFrom = new \DateTimeImmutable('2026-03-01');
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $response = $sut($request, $user);

        $this->assertSame(1, $response->totalTrainingSessions);
        $this->assertSame(1, $response->totalTrainingAttendances);
        $this->assertSame(1, $response->totalProtocolAccesses);
        $this->assertSame(['Higiene'], $response->attendancesBySession->labels);
        $this->assertSame(['04/2026'], $response->sessionsByMonth->labels);
    }

    public function testUnauthorizedUser(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $sut = $this->createSUT();

        $request = new GetTrainingDashboardRequest(accountId: $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('other-owner', Role::Owner, new AccountId('another-account-id'));

        $this->expectException(UnauthorizedActionException::class);

        $sut($request, $user);
    }

    private function createSUT(): GetTrainingDashboardService
    {
        return new GetTrainingDashboardService(
            repository: $this->createTrainingSessionRepository(),
            authorizer: new TrainingSessionAuthorizer(),
            protocolAuthorizer: new ProtocolAuthorizer(),
            trainingAttendanceRepository: $this->createTrainingAttendanceRepository(),
            protocolRepository: $this->createProtocolRepository(),
            protocolAccessRepository: $this->createProtocolAccessRepository(),
        );
    }

    private function createTrainingSessionRepository(): TrainingSessionRepository
    {
        $repository = TrainingSessionRepositoryMother::inMemory();
        $repository->add(TrainingSessionMother::from(
            title: 'Manipulación de alimentos',
            id: self::SESSION_ONE_ID,
            sessionDate: new \DateTimeImmutable('2026-02-10'),
        ));
        $repository->add(TrainingSessionMother::from(
            title: 'Higiene',
            id: self::SESSION_TWO_ID,
            sessionDate: new \DateTimeImmutable('2026-04-05'),
        ));
        $repository->add(TrainingSessionMother::forAccount('another-account-id'));

        return $repository;
    }

    private function createTrainingAttendanceRepository(): TrainingAttendanceRepository
    {
        $repository = TrainingAttendanceRepositoryMother::inMemory();
        $repository->add(TrainingAttendanceMother::from(
            trainingSessionId: self::SESSION_ONE_ID,
            employeeId: (string) EmployeeId::generate(),
            attendedAt: new \DateTimeImmutable('2026-02-10'),
        ));
        $repository->add(TrainingAttendanceMother::from(
            trainingSessionId: self::SESSION_ONE_ID,
            employeeId: (string) EmployeeId::generate(),
            attendedAt: new \DateTimeImmutable('2026-02-10'),
        ));
        $repository->add(TrainingAttendanceMother::from(
            trainingSessionId: self::SESSION_TWO_ID,
            employeeId: (string) EmployeeId::generate(),
            attendedAt: new \DateTimeImmutable('2026-04-05'),
        ));
        $repository->add(TrainingAttendanceMother::forAccount('another-account-id'));

        return $repository;
    }

    private function createProtocolRepository(): ProtocolRepository
    {
        $repository = ProtocolRepositoryMother::inMemory();
        $repository->add(ProtocolMother::fromNameAndId('Protocolo A', self::PROTOCOL_ONE_ID));
        $repository->add(ProtocolMother::fromNameAndId('Protocolo B', self::PROTOCOL_TWO_ID));
        $repository->add(ProtocolMother::forAccount('another-account-id'));

        return $repository;
    }

    private function createProtocolAccessRepository(): ProtocolAccessRepository
    {
        $repository = ProtocolAccessRepositoryMother::inMemory();
        $repository->add(ProtocolAccessMother::forProtocolAndEmployeeAt(
            protocolId: self::PROTOCOL_ONE_ID,
            employeeId: (string) EmployeeId::generate(),
            accessedAt: new \DateTimeImmutable('2026-02-15'),
        ));
        $repository->add(ProtocolAccessMother::forProtocolAndEmployeeAt(
            protocolId: self::PROTOCOL_ONE_ID,
            employeeId: (string) EmployeeId::generate(),
            accessedAt: new \DateTimeImmutable('2026-04-10'),
        ));

        return $repository;
    }
}
