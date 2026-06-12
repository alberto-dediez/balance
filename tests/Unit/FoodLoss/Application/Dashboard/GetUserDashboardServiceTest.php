<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Dashboard;

use App\FoodLoss\Application\Dashboard\GetUserDashboardRequest;
use App\FoodLoss\Application\Dashboard\GetUserDashboardService;
use App\FoodLoss\Application\Dashboard\UserDashboardAuthorizer;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\DeliveryLog\FoodType;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionId;
use App\FoodLoss\Domain\Model\Surplus\SurplusDestination;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryDeliveryLogRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryDoggyBagExceptionRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryDoggyBagSignRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryProtocolAccessRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryProtocolRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemorySurplusRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryTrainingAttendanceRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryTrainingSessionRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Protocol\ProtocolMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Protocol\ProtocolRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetUserDashboardServiceTest extends TestCase
{
    private const ACCOUNT_ID = 'account-id-test';

    public function testSurplusKpis(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $surplusRepository = SurplusRepositoryMother::inMemory();

        $donated = SurplusMother::withAccountId($accountId);
        $donated->assessHumanDonation(true);
        $surplusRepository->add($donated);

        $eliminated = SurplusMother::withAccountId($accountId);
        $eliminated->assessElimination(true);
        $surplusRepository->add($eliminated);

        $surplusRepository->add(SurplusMother::withAccountId($accountId));
        $surplusRepository->add(SurplusMother::random());

        $sut = $this->createSUT(surplusRepository: $surplusRepository);

        $response = $sut(new GetUserDashboardRequest(accountId: $accountId), $this->user());

        $this->assertSame(3, $response->totalSurpluses);
        $this->assertSame(15.0, $response->surplusKg);
        $this->assertSame(0.0, $response->surplusRations);
        $this->assertSame(1, $response->recoveredSurpluses);
        $this->assertSame(1, $response->eliminatedSurpluses);
        $this->assertSame(1, $response->unassessedSurpluses);
        $this->assertSame(33.3, $response->recoveredPercentage());

        $countByDestination = array_combine($response->surplusesByDestination->labels, $response->surplusesByDestination->values);
        $this->assertSame(1, $countByDestination[SurplusDestination::HumanDonation->value]);
        $this->assertSame(1, $countByDestination[SurplusDestination::Elimination->value]);
        $this->assertSame(1, $countByDestination[GetUserDashboardService::UNASSESSED]);
    }

    public function testFiltersSurplusesByDateRange(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $surplusRepository = SurplusRepositoryMother::inMemory();
        $surplusRepository->add(SurplusMother::withAccountIdAndDate($accountId, new \DateTimeImmutable('2026-01-15')));
        $surplusRepository->add(SurplusMother::withAccountIdAndDate($accountId, new \DateTimeImmutable('2026-03-15')));

        $sut = $this->createSUT(surplusRepository: $surplusRepository);

        $request = new GetUserDashboardRequest(
            accountId: $accountId,
            dateFrom: new \DateTimeImmutable('2026-02-01'),
            dateTo: new \DateTimeImmutable('2026-04-01'),
        );

        $response = $sut($request, $this->user());

        $this->assertSame(1, $response->totalSurpluses);
    }

    public function testDeliveryLogKpis(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();
        $deliveryLogRepository->add(DeliveryLogMother::from(productType: FoodType::Vegetables, id: (string) DeliveryLogId::generate(), accountId: self::ACCOUNT_ID));
        $deliveryLogRepository->add(DeliveryLogMother::from(productType: FoodType::Fruit, id: (string) DeliveryLogId::generate(), accountId: self::ACCOUNT_ID));
        $deliveryLogRepository->add(DeliveryLogMother::from(productType: FoodType::Meat, id: (string) DeliveryLogId::generate(), accountId: self::ACCOUNT_ID, quantity: 5.0, unit: DeliveryLogUnit::Rations));
        $deliveryLogRepository->add(DeliveryLogMother::random());

        $sut = $this->createSUT(deliveryLogRepository: $deliveryLogRepository);

        $response = $sut(new GetUserDashboardRequest(accountId: $accountId), $this->user());

        $this->assertSame(3, $response->totalDeliveryLogs);
        $this->assertSame(20.0, $response->deliveredKg);
        $this->assertSame(5.0, $response->deliveredRations);
    }

    public function testDoggyBagKpis(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $doggyBagSignRepository = DoggyBagSignRepositoryMother::inMemory();
        $doggyBagSignRepository->add(DoggyBagSignMother::forAccount(self::ACCOUNT_ID));
        $doggyBagSignRepository->add(DoggyBagSignMother::forAccount(self::ACCOUNT_ID));
        $doggyBagSignRepository->add(DoggyBagSignMother::random());

        $doggyBagExceptionRepository = DoggyBagExceptionRepositoryMother::inMemory();
        $doggyBagExceptionRepository->add(DoggyBagExceptionMother::from(
            description: 'exception 1',
            criticalPointId: (string) \App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId::generate(),
            accountId: self::ACCOUNT_ID,
            id: (string) DoggyBagExceptionId::generate(),
        ));
        $doggyBagExceptionRepository->add(DoggyBagExceptionMother::random());

        $sut = $this->createSUT(doggyBagSignRepository: $doggyBagSignRepository, doggyBagExceptionRepository: $doggyBagExceptionRepository);

        $response = $sut(new GetUserDashboardRequest(accountId: $accountId), $this->user());

        $this->assertSame(2, $response->totalDoggyBagSigns);
        $this->assertSame(1, $response->totalDoggyBagExceptions);
    }

    public function testProtocolKpis(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $protocolRepository = ProtocolRepositoryMother::inMemory();

        $readProtocol = ProtocolMother::forAccount(self::ACCOUNT_ID);
        $protocolRepository->add($readProtocol);
        $protocolRepository->add(ProtocolMother::forAccount(self::ACCOUNT_ID));

        $protocolAccessRepository = ProtocolAccessRepositoryMother::inMemory();
        $protocolAccessRepository->add(ProtocolAccessMother::forProtocolAndEmployee(
            (string) $readProtocol->id(),
            (string) EmployeeId::generate(),
            self::ACCOUNT_ID,
        ));

        $sut = $this->createSUT(protocolRepository: $protocolRepository, protocolAccessRepository: $protocolAccessRepository);

        $response = $sut(new GetUserDashboardRequest(accountId: $accountId), $this->user());

        $this->assertSame(2, $response->totalProtocols);
        $this->assertSame(1, $response->readProtocols);
        $this->assertSame(1, $response->unreadProtocols());
        $this->assertSame(50.0, $response->readProtocolsPercentage());
        $this->assertSame(1, $response->totalProtocolAccesses);
    }

    public function testTrainingKpis(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $trainingSessionRepository = TrainingSessionRepositoryMother::inMemory();
        $trainingSessionRepository->add(TrainingSessionMother::forAccount(self::ACCOUNT_ID));
        $trainingSessionRepository->add(TrainingSessionMother::from(title: 'Sesión futura', id: (string) \App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId::generate(), accountId: self::ACCOUNT_ID, sessionDate: new \DateTimeImmutable('+1 month')));
        $trainingSessionRepository->add(TrainingSessionMother::random());

        $trainingAttendanceRepository = TrainingAttendanceRepositoryMother::inMemory();
        $trainingAttendanceRepository->add(TrainingAttendanceMother::forAccount(self::ACCOUNT_ID));
        $trainingAttendanceRepository->add(TrainingAttendanceMother::forAccount(self::ACCOUNT_ID));

        $sut = $this->createSUT(trainingSessionRepository: $trainingSessionRepository, trainingAttendanceRepository: $trainingAttendanceRepository);

        $response = $sut(new GetUserDashboardRequest(accountId: $accountId), $this->user());

        $this->assertSame(2, $response->totalTrainingSessions);
        $this->assertSame(1, $response->upcomingTrainingSessions);
        $this->assertSame(2, $response->totalTrainingAttendances);
    }

    public function testUnauthorizedUser(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $sut = $this->createSUT();

        $request = new GetUserDashboardRequest(accountId: $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('other-user', Role::User, new AccountId('another-account-id'));

        $this->expectException(UnauthorizedActionException::class);

        $sut($request, $user);
    }

    private function user(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId(self::ACCOUNT_ID));
    }

    private function createSUT(
        ?InMemorySurplusRepository $surplusRepository = null,
        ?InMemoryDeliveryLogRepository $deliveryLogRepository = null,
        ?InMemoryDoggyBagSignRepository $doggyBagSignRepository = null,
        ?InMemoryDoggyBagExceptionRepository $doggyBagExceptionRepository = null,
        ?InMemoryProtocolRepository $protocolRepository = null,
        ?InMemoryProtocolAccessRepository $protocolAccessRepository = null,
        ?InMemoryTrainingSessionRepository $trainingSessionRepository = null,
        ?InMemoryTrainingAttendanceRepository $trainingAttendanceRepository = null,
    ): GetUserDashboardService {
        return new GetUserDashboardService(
            authorizer: new UserDashboardAuthorizer(),
            surplusRepository: $surplusRepository ?? SurplusRepositoryMother::inMemory(),
            deliveryLogRepository: $deliveryLogRepository ?? DeliveryLogRepositoryMother::inMemory(),
            doggyBagSignRepository: $doggyBagSignRepository ?? DoggyBagSignRepositoryMother::inMemory(),
            doggyBagExceptionRepository: $doggyBagExceptionRepository ?? DoggyBagExceptionRepositoryMother::inMemory(),
            protocolRepository: $protocolRepository ?? ProtocolRepositoryMother::inMemory(),
            protocolAccessRepository: $protocolAccessRepository ?? ProtocolAccessRepositoryMother::inMemory(),
            trainingSessionRepository: $trainingSessionRepository ?? TrainingSessionRepositoryMother::inMemory(),
            trainingAttendanceRepository: $trainingAttendanceRepository ?? TrainingAttendanceRepositoryMother::inMemory(),
        );
    }
}
