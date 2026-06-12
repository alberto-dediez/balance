<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\DeliveryLog\Dashboard;

use App\FoodLoss\Application\DeliveryLog\Dashboard\GetDonationDashboardRequest;
use App\FoodLoss\Application\DeliveryLog\Dashboard\GetDonationDashboardService;
use App\FoodLoss\Application\DeliveryLog\DeliveryLogAuthorizer;
use App\FoodLoss\Application\DonationAgreement\DonationAgreementAuthorizer;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\DeliveryLog\FoodType;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementId;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationMother;
use App\Tests\Unit\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetDonationDashboardServiceTest extends TestCase
{
    private const ACCOUNT_ID = 'account-id-test';
    private const PROPERTY_ID = '00000000-0000-0000-0000-000000000010';
    private const FOOD_BANK_ID = '00000000-0000-0000-0000-000000000001';
    private const NGO_ID = '00000000-0000-0000-0000-000000000002';

    public function testUseCase(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();
        $deliveryLogRepository->add(DeliveryLogMother::from(
            productType: FoodType::Fruit,
            id: (string) DeliveryLogId::generate(),
            accountId: self::ACCOUNT_ID,
            recipientOrganizationId: self::FOOD_BANK_ID,
            propertyId: self::PROPERTY_ID,
            quantity: 10.0,
            unit: DeliveryLogUnit::Kg,
            date: new \DateTimeImmutable('2026-01-15'),
        ));
        $deliveryLogRepository->add(DeliveryLogMother::from(
            productType: FoodType::Vegetables,
            id: (string) DeliveryLogId::generate(),
            accountId: self::ACCOUNT_ID,
            recipientOrganizationId: self::NGO_ID,
            propertyId: self::PROPERTY_ID,
            quantity: 5.0,
            unit: DeliveryLogUnit::Rations,
            date: new \DateTimeImmutable('2026-03-20'),
        ));
        $deliveryLogRepository->add(DeliveryLogMother::random());

        $donationAgreementRepository = DonationAgreementRepositoryMother::inMemory();
        $donationAgreementRepository->add($this->agreement(new \DateTimeImmutable('-2 months'), new \DateTimeImmutable('+6 months')));
        $donationAgreementRepository->add($this->agreement(new \DateTimeImmutable('-2 months'), new \DateTimeImmutable('+10 days')));
        $donationAgreementRepository->add($this->agreement(new \DateTimeImmutable('-2 months'), new \DateTimeImmutable('-1 day')));
        $donationAgreementRepository->add($this->agreement(new \DateTimeImmutable('+1 month'), new \DateTimeImmutable('+6 months')));
        $donationAgreementRepository->add(DonationAgreementMother::random());

        $sut = $this->createSUT($deliveryLogRepository, $donationAgreementRepository);

        $request = new GetDonationDashboardRequest(accountId: $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $response = $sut($request, $user);

        $this->assertSame(2, $response->totalDeliveryLogs);
        $this->assertSame(10.0, $response->totalKg);
        $this->assertSame(5.0, $response->totalRations);
        $this->assertSame(DeliveryLogUnit::Kg, $response->displayUnit);
        $this->assertSame(12.0, $response->totalQuantity);

        $this->assertSame(['01/2026', '02/2026', '03/2026'], $response->quantityByMonth->labels);
        $this->assertSame([10.0, 0.0, 2.0], $response->quantityByMonth->values);

        $productTypeCounts = array_combine($response->byProductType->labels, $response->byProductType->values);
        $this->assertSame(1, $productTypeCounts['fruit']);
        $this->assertSame(1, $productTypeCounts['vegetables']);

        $this->assertSame(['Banco de Alimentos', 'ONG Test'], $response->byRecipientOrganization->labels);
        $this->assertSame([10.0, 2.0], $response->byRecipientOrganization->values);

        $this->assertSame(['Hotel Test'], $response->byProperty->labels);
        $this->assertSame([2], $response->byProperty->values);

        $this->assertSame(2, $response->activeDonationAgreements);
        $this->assertSame(1, $response->expiringSoonDonationAgreements);

        $statusCounts = array_combine($response->agreementsByStatus->labels, $response->agreementsByStatus->values);
        $this->assertSame(1, $statusCounts[GetDonationDashboardService::AGREEMENT_ACTIVE]);
        $this->assertSame(1, $statusCounts[GetDonationDashboardService::AGREEMENT_EXPIRING_SOON]);
        $this->assertSame(1, $statusCounts[GetDonationDashboardService::AGREEMENT_EXPIRED]);
        $this->assertSame(1, $statusCounts[GetDonationDashboardService::AGREEMENT_NOT_STARTED]);
        $this->assertSame(GetDonationDashboardService::AGREEMENT_STATUS_COLORS, $response->agreementsByStatus->colors);

        $this->assertSame(2, $response->totalRecipientOrganizations);

        $organizationTypeCounts = array_combine($response->organizationsByType->labels, $response->organizationsByType->values);
        $this->assertSame(1, $organizationTypeCounts['FoodBank']);
        $this->assertSame(1, $organizationTypeCounts['Ngo']);
        $this->assertSame(0, $organizationTypeCounts['SocialDining']);
    }

    public function testConvertsKgToRationsWithACustomConversionFactor(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();
        $deliveryLogRepository->add(DeliveryLogMother::from(
            productType: FoodType::Fruit,
            id: (string) DeliveryLogId::generate(),
            accountId: self::ACCOUNT_ID,
            quantity: 10.0,
            unit: DeliveryLogUnit::Kg,
        ));
        $deliveryLogRepository->add(DeliveryLogMother::from(
            productType: FoodType::Fruit,
            id: (string) DeliveryLogId::generate(),
            accountId: self::ACCOUNT_ID,
            quantity: 5.0,
            unit: DeliveryLogUnit::Rations,
        ));

        $sut = $this->createSUT($deliveryLogRepository, DonationAgreementRepositoryMother::inMemory());

        $request = new GetDonationDashboardRequest(accountId: $accountId);
        $request->displayUnit = DeliveryLogUnit::Rations;
        $request->kgPerRation = 0.5;
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $response = $sut($request, $user);

        $this->assertSame(DeliveryLogUnit::Rations, $response->displayUnit);
        $this->assertSame(25.0, $response->totalQuantity);
    }

    public function testFiltersAgreementsByRecipientOrganization(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $donationAgreementRepository = DonationAgreementRepositoryMother::inMemory();
        $donationAgreementRepository->add($this->agreement(new \DateTimeImmutable('-2 months'), new \DateTimeImmutable('+6 months'), self::FOOD_BANK_ID));
        $donationAgreementRepository->add($this->agreement(new \DateTimeImmutable('-2 months'), new \DateTimeImmutable('+6 months'), self::NGO_ID));

        $sut = $this->createSUT(DeliveryLogRepositoryMother::inMemory(), $donationAgreementRepository);

        $request = new GetDonationDashboardRequest(accountId: $accountId, recipientOrganizationId: new RecipientOrganizationId(self::FOOD_BANK_ID));
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $response = $sut($request, $user);

        $this->assertSame(1, $response->activeDonationAgreements);
    }

    public function testUnauthorizedUser(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $sut = $this->createSUT(DeliveryLogRepositoryMother::inMemory(), DonationAgreementRepositoryMother::inMemory());

        $request = new GetDonationDashboardRequest(accountId: $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('other-owner', Role::Owner, new AccountId('another-account-id'));

        $this->expectException(UnauthorizedActionException::class);

        $sut($request, $user);
    }

    private function agreement(\DateTimeImmutable $startDate, \DateTimeImmutable $expirationDate, string $recipientOrganizationId = self::FOOD_BANK_ID): DonationAgreement
    {
        return DonationAgreementMother::from(
            id: (string) DonationAgreementId::generate(),
            recipientOrganizationId: $recipientOrganizationId,
            accountId: self::ACCOUNT_ID,
            startDate: $startDate,
            expirationDate: $expirationDate,
            uploadedBy: (string) UserId::generate(),
            path: 'path/to/file.pdf',
        );
    }

    private function createSUT(DeliveryLogRepository $deliveryLogRepository, DonationAgreementRepository $donationAgreementRepository): GetDonationDashboardService
    {
        $recipientOrganizationRepository = RecipientOrganizationRepositoryMother::inMemory();
        $recipientOrganizationRepository->add(RecipientOrganizationMother::from(name: 'Banco de Alimentos', id: self::FOOD_BANK_ID));
        $recipientOrganizationRepository->add(RecipientOrganizationMother::from(name: 'ONG Test', id: self::NGO_ID, type: RecipientOrganizationType::Ngo));
        $recipientOrganizationRepository->add(RecipientOrganizationMother::from(name: 'Otra Entidad', id: (string) RecipientOrganizationId::generate(), accountId: 'another-account-id'));

        $propertyRepository = PropertyRepositoryMother::inMemory();
        $propertyRepository->add(PropertyMother::fromNameAndId('Hotel Test', self::PROPERTY_ID));

        return new GetDonationDashboardService(
            repository: $deliveryLogRepository,
            authorizer: new DeliveryLogAuthorizer(),
            donationAgreementAuthorizer: new DonationAgreementAuthorizer(),
            donationAgreementRepository: $donationAgreementRepository,
            recipientOrganizationRepository: $recipientOrganizationRepository,
            propertyRepository: $propertyRepository,
        );
    }
}
