<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Dashboard;

use App\FoodLoss\Application\Dashboard\GetOwnerDashboardRequest;
use App\FoodLoss\Application\Dashboard\GetOwnerDashboardService;
use App\FoodLoss\Application\Dashboard\OwnerDashboardAuthorizer;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\CriticalPoint\SupplyChainStage;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\DeliveryLog\FoodType;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceType;
use App\FoodLoss\Domain\Model\Packaging\PackagingId;
use App\FoodLoss\Domain\Model\Packaging\PackagingType;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionStatus;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\Surplus\SurplusDestination;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryCriticalPointRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryDeliveryLogRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryDoggyBagSignRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryDonationAgreementRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryIncidenceRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryPackagingRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryPreventionPlanRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryProtocolAccessRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryProtocolRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemorySurplusRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryTrainingAttendanceRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryTrainingSessionRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointMother;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Incidence\IncidenceMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Incidence\IncidenceRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Packaging\PackagingMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Packaging\PackagingRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepositoryMother;
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

class GetOwnerDashboardServiceTest extends TestCase
{
    private const ACCOUNT_ID = 'account-id-test';

    public function testCriticalPointsKpis(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $criticalPointRepository = CriticalPointRepositoryMother::inMemory();

        $withPrevention = CriticalPointMother::fromNameAndAccountAndProperty('Cocina', $accountId, PropertyId::generate());
        $withPrevention->activatePrevention();
        $criticalPointRepository->add($withPrevention);

        $criticalPointRepository->add(CriticalPointMother::fromNameAndAccountAndProperty('Buffet', $accountId, PropertyId::generate()));
        $criticalPointRepository->add(CriticalPointMother::random());

        $sut = $this->createSUT(criticalPointRepository: $criticalPointRepository);

        $response = $sut(new GetOwnerDashboardRequest(accountId: $accountId), $this->owner());

        $this->assertSame(2, $response->totalCriticalPoints);

        $countByPriority = array_combine($response->criticalPointsByPriority->labels, $response->criticalPointsByPriority->values);
        $this->assertSame(1, $countByPriority[Priority::Prevention->value]);
        $this->assertSame(0, $countByPriority[Priority::HumanDonation->value]);
        $this->assertSame(0, $countByPriority[Priority::Composting->value]);

        $countByStage = array_combine($response->criticalPointsBySupplyChainStage->labels, $response->criticalPointsBySupplyChainStage->values);
        $this->assertSame(2, $countByStage[SupplyChainStage::Receiving->value]);
        $this->assertSame(0, $countByStage[SupplyChainStage::Storage->value]);
    }

    public function testPreventionPlanKpis(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $preventionPlanRepository = PreventionPlanRepositoryMother::inMemory();

        $completed = PreventionPlanMother::withDraftApprovedStatus('Plan completado', (string) PreventionPlanId::generate(), self::ACCOUNT_ID);
        $completed->markAsCompleted();
        $preventionPlanRepository->add($completed);

        $preventionPlanRepository->add(PreventionPlanMother::forAccount(self::ACCOUNT_ID));
        $preventionPlanRepository->add(PreventionPlanMother::random());

        $sut = $this->createSUT(preventionPlanRepository: $preventionPlanRepository);

        $response = $sut(new GetOwnerDashboardRequest(accountId: $accountId), $this->owner());

        $this->assertSame(2, $response->totalPreventionPlans);
        $this->assertTrue($response->hasCompletedPreventionPlan);

        $countByStatus = array_combine($response->preventionPlansByStatus->labels, $response->preventionPlansByStatus->values);
        $this->assertSame(1, $countByStatus[PreventionPlanVersionStatus::Completed->value]);
        $this->assertSame(1, $countByStatus[PreventionPlanVersionStatus::InProgress->value]);
    }

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

        $response = $sut(new GetOwnerDashboardRequest(accountId: $accountId), $this->owner());

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
        $this->assertSame(1, $countByDestination[GetOwnerDashboardService::UNASSESSED]);
    }

    public function testFiltersSurplusesByDateRange(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $surplusRepository = SurplusRepositoryMother::inMemory();
        $surplusRepository->add(SurplusMother::withAccountIdAndDate($accountId, new \DateTimeImmutable('2026-01-15')));
        $surplusRepository->add(SurplusMother::withAccountIdAndDate($accountId, new \DateTimeImmutable('2026-03-15')));

        $sut = $this->createSUT(surplusRepository: $surplusRepository);

        $request = new GetOwnerDashboardRequest(
            accountId: $accountId,
            dateFrom: new \DateTimeImmutable('2026-02-01'),
            dateTo: new \DateTimeImmutable('2026-04-01'),
        );

        $response = $sut($request, $this->owner());

        $this->assertSame(1, $response->totalSurpluses);
    }

    public function testDonationKpis(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $donationAgreementRepository = DonationAgreementRepositoryMother::inMemory();
        $donationAgreementRepository->add($this->donationAgreement('-1 month', '+6 months'));
        $donationAgreementRepository->add($this->donationAgreement('-1 month', '+10 days'));
        $donationAgreementRepository->add($this->donationAgreement('-1 year', '-2 days'));
        $donationAgreementRepository->add(DonationAgreementMother::random());

        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();
        $deliveryLogRepository->add(DeliveryLogMother::from(productType: FoodType::Vegetables, id: (string) DeliveryLogId::generate(), accountId: self::ACCOUNT_ID));
        $deliveryLogRepository->add(DeliveryLogMother::from(productType: FoodType::Fruit, id: (string) DeliveryLogId::generate(), accountId: self::ACCOUNT_ID));
        $deliveryLogRepository->add(DeliveryLogMother::from(productType: FoodType::Meat, id: (string) DeliveryLogId::generate(), accountId: self::ACCOUNT_ID, quantity: 5.0, unit: DeliveryLogUnit::Rations));
        $deliveryLogRepository->add(DeliveryLogMother::random());

        $sut = $this->createSUT(donationAgreementRepository: $donationAgreementRepository, deliveryLogRepository: $deliveryLogRepository);

        $response = $sut(new GetOwnerDashboardRequest(accountId: $accountId), $this->owner());

        $this->assertSame(2, $response->activeDonationAgreements);
        $this->assertSame(1, $response->expiringSoonDonationAgreements);
        $this->assertSame(1, $response->expiredDonationAgreements);
        $this->assertSame(3, $response->totalDeliveryLogs);
        $this->assertSame(20.0, $response->deliveredKg);
        $this->assertSame(5.0, $response->deliveredRations);
    }

    public function testPackagingAndDoggyBagSignKpis(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $packagingRepository = PackagingRepositoryMother::inMemory();

        $reusable = PackagingMother::from(name: 'Tupper', id: (string) PackagingId::generate(), accountId: self::ACCOUNT_ID, packagingType: PackagingType::Reusable);
        $reusable->setPropertyStock(PropertyId::generate(), 25);
        $reusable->setPropertyStock(PropertyId::generate(), 10);
        $packagingRepository->add($reusable);

        $compostable = PackagingMother::from(name: 'Caja', id: (string) PackagingId::generate(), accountId: self::ACCOUNT_ID, packagingType: PackagingType::Compostable);
        $compostable->setPropertyStock(PropertyId::generate(), 5);
        $packagingRepository->add($compostable);

        $doggyBagSignRepository = DoggyBagSignRepositoryMother::inMemory();
        $doggyBagSignRepository->add(DoggyBagSignMother::forAccount(self::ACCOUNT_ID));
        $doggyBagSignRepository->add(DoggyBagSignMother::random());

        $sut = $this->createSUT(packagingRepository: $packagingRepository, doggyBagSignRepository: $doggyBagSignRepository);

        $response = $sut(new GetOwnerDashboardRequest(accountId: $accountId), $this->owner());

        $this->assertSame(2, $response->totalPackagings);
        $this->assertSame(40, $response->totalPackagingStock);

        $stockByType = array_combine($response->packagingStockByType->labels, $response->packagingStockByType->values);
        $this->assertSame(35, $stockByType[PackagingType::Reusable->value]);
        $this->assertSame(5, $stockByType[PackagingType::Compostable->value]);
        $this->assertSame(0, $stockByType[PackagingType::Recyclable->value]);

        $this->assertSame(1, $response->totalDoggyBagSigns);
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

        $response = $sut(new GetOwnerDashboardRequest(accountId: $accountId), $this->owner());

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
        $trainingSessionRepository->add(TrainingSessionMother::random());

        $trainingAttendanceRepository = TrainingAttendanceRepositoryMother::inMemory();
        $trainingAttendanceRepository->add(TrainingAttendanceMother::forAccount(self::ACCOUNT_ID));
        $trainingAttendanceRepository->add(TrainingAttendanceMother::forAccount(self::ACCOUNT_ID));

        $sut = $this->createSUT(trainingSessionRepository: $trainingSessionRepository, trainingAttendanceRepository: $trainingAttendanceRepository);

        $response = $sut(new GetOwnerDashboardRequest(accountId: $accountId), $this->owner());

        $this->assertSame(1, $response->totalTrainingSessions);
        $this->assertSame(2, $response->totalTrainingAttendances);
    }

    public function testIncidenceKpis(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $incidenceRepository = IncidenceRepositoryMother::inMemory();
        $incidenceRepository->add(IncidenceMother::from(name: 'Avería cámara', id: (string) IncidenceId::generate()));
        $incidenceRepository->add(IncidenceMother::from(name: 'Avería horno', id: (string) IncidenceId::generate()));
        $incidenceRepository->add(IncidenceMother::from(name: 'Avería ajena', id: (string) IncidenceId::generate(), accountId: 'another-account-id'));

        $sut = $this->createSUT(incidenceRepository: $incidenceRepository);

        $response = $sut(new GetOwnerDashboardRequest(accountId: $accountId), $this->owner());

        $this->assertSame(2, $response->totalIncidences);

        $countByType = array_combine($response->incidencesByType->labels, $response->incidencesByType->values);
        $this->assertSame(2, $countByType[IncidenceType::MachineryFailure->value]);
        $this->assertSame(0, $countByType[IncidenceType::SpoiledFood->value]);
    }

    public function testUnauthorizedUser(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $sut = $this->createSUT();

        $request = new GetOwnerDashboardRequest(accountId: $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('other-owner', Role::Owner, new AccountId('another-account-id'));

        $this->expectException(UnauthorizedActionException::class);

        $sut($request, $user);
    }

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId(self::ACCOUNT_ID));
    }

    private function donationAgreement(string $startModifier, string $expirationModifier): DonationAgreement
    {
        return DonationAgreementMother::from(
            id: (string) DonationAgreementId::generate(),
            recipientOrganizationId: (string) RecipientOrganizationId::generate(),
            accountId: self::ACCOUNT_ID,
            startDate: new \DateTimeImmutable($startModifier),
            expirationDate: new \DateTimeImmutable($expirationModifier),
            uploadedBy: (string) UserId::generate(),
            path: 'path/to/file.pdf',
        );
    }

    private function createSUT(
        ?InMemoryCriticalPointRepository $criticalPointRepository = null,
        ?InMemoryPreventionPlanRepository $preventionPlanRepository = null,
        ?InMemorySurplusRepository $surplusRepository = null,
        ?InMemoryDonationAgreementRepository $donationAgreementRepository = null,
        ?InMemoryDeliveryLogRepository $deliveryLogRepository = null,
        ?InMemoryPackagingRepository $packagingRepository = null,
        ?InMemoryDoggyBagSignRepository $doggyBagSignRepository = null,
        ?InMemoryProtocolRepository $protocolRepository = null,
        ?InMemoryProtocolAccessRepository $protocolAccessRepository = null,
        ?InMemoryTrainingSessionRepository $trainingSessionRepository = null,
        ?InMemoryTrainingAttendanceRepository $trainingAttendanceRepository = null,
        ?InMemoryIncidenceRepository $incidenceRepository = null,
    ): GetOwnerDashboardService {
        return new GetOwnerDashboardService(
            authorizer: new OwnerDashboardAuthorizer(),
            criticalPointRepository: $criticalPointRepository ?? CriticalPointRepositoryMother::inMemory(),
            preventionPlanRepository: $preventionPlanRepository ?? PreventionPlanRepositoryMother::inMemory(),
            surplusRepository: $surplusRepository ?? SurplusRepositoryMother::inMemory(),
            donationAgreementRepository: $donationAgreementRepository ?? DonationAgreementRepositoryMother::inMemory(),
            deliveryLogRepository: $deliveryLogRepository ?? DeliveryLogRepositoryMother::inMemory(),
            packagingRepository: $packagingRepository ?? PackagingRepositoryMother::inMemory(),
            doggyBagSignRepository: $doggyBagSignRepository ?? DoggyBagSignRepositoryMother::inMemory(),
            protocolRepository: $protocolRepository ?? ProtocolRepositoryMother::inMemory(),
            protocolAccessRepository: $protocolAccessRepository ?? ProtocolAccessRepositoryMother::inMemory(),
            trainingSessionRepository: $trainingSessionRepository ?? TrainingSessionRepositoryMother::inMemory(),
            trainingAttendanceRepository: $trainingAttendanceRepository ?? TrainingAttendanceRepositoryMother::inMemory(),
            incidenceRepository: $incidenceRepository ?? IncidenceRepositoryMother::inMemory(),
        );
    }
}
