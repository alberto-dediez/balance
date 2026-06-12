<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\Dashboard;

use App\FoodLoss\Application\Dashboard\DashboardChart;
use App\FoodLoss\Application\DeliveryLog\DeliveryLogAuthorizer;
use App\FoodLoss\Application\DonationAgreement\DonationAgreementAuthorizer;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLog;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementFilter;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementId;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganization;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationFilter;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationType;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class GetDonationDashboardService
{
    public const AGREEMENT_ACTIVE = 'Active';

    public const AGREEMENT_EXPIRING_SOON = 'ExpiringSoon';

    public const AGREEMENT_EXPIRED = 'Expired';

    public const AGREEMENT_NOT_STARTED = 'NotStarted';

    public const AGREEMENT_STATUS_COLORS = [
        'var(--bs-success)',
        'var(--bs-warning)',
        'var(--bs-danger)',
        'var(--bs-secondary)',
    ];

    /** @var array<string, RecipientOrganization|null> */
    private array $recipientOrganizations = [];

    /** @var array<string, Property|null> */
    private array $properties = [];

    public function __construct(
        private readonly DeliveryLogRepository $repository,
        private readonly DeliveryLogAuthorizer $authorizer,
        private readonly DonationAgreementAuthorizer $donationAgreementAuthorizer,
        private readonly DonationAgreementRepository $donationAgreementRepository,
        private readonly RecipientOrganizationRepository $recipientOrganizationRepository,
        private readonly PropertyRepository $propertyRepository,
    ) {}

    public function __invoke(GetDonationDashboardRequest $request, User $user): GetDonationDashboardResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new DeliveryLog(id: DeliveryLogId::generate(), accountId: $request->accountId), SecurityAction::List, $user);
        $this->donationAgreementAuthorizer->denyUnlessCanDoThat(new DonationAgreement(id: DonationAgreementId::generate(), accountId: $request->accountId), SecurityAction::List, $user);

        $request->limit = 0;
        $request->page = 0;

        $deliveryLogs = $this->repository->findByFilter($request);

        $displayUnit = $request->displayUnit ?? DeliveryLogUnit::Kg;
        $kgPerRation = null !== $request->kgPerRation && $request->kgPerRation > 0
            ? $request->kgPerRation
            : DeliveryLogUnit::MITECO_KG_PER_RATION;

        $totalKg = 0.0;
        $totalRations = 0.0;
        $totalQuantity = 0.0;

        $quantityByMonth = [];
        $countByProductType = [];
        $quantityByRecipientOrganization = [];
        $countByProperty = [];

        foreach ($deliveryLogs as $deliveryLog) {
            if (DeliveryLogUnit::Kg === $deliveryLog->unit()) {
                $totalKg += $deliveryLog->quantity();
            } else {
                $totalRations += $deliveryLog->quantity();
            }

            $quantity = $this->convert($deliveryLog->quantity(), $deliveryLog->unit(), $displayUnit, $kgPerRation);
            $totalQuantity += $quantity;

            $month = $deliveryLog->date()->format('Y-m');
            $quantityByMonth[$month] = ($quantityByMonth[$month] ?? 0.0) + $quantity;

            $productType = $deliveryLog->productType()->value;
            $countByProductType[$productType] = ($countByProductType[$productType] ?? 0) + 1;

            if ($deliveryLog->recipientOrganizationId() instanceof RecipientOrganizationId) {
                $recipientOrganizationName = $this->recipientOrganizationName($deliveryLog->recipientOrganizationId());
                $quantityByRecipientOrganization[$recipientOrganizationName] = round(($quantityByRecipientOrganization[$recipientOrganizationName] ?? 0.0) + $quantity, 2);
            }

            if ($deliveryLog->propertyId() instanceof PropertyId) {
                $propertyName = $this->propertyName($deliveryLog->propertyId());
                $countByProperty[$propertyName] = ($countByProperty[$propertyName] ?? 0) + 1;
            }
        }

        arsort($countByProductType);
        arsort($quantityByRecipientOrganization);
        arsort($countByProperty);

        ksort($quantityByMonth);

        $response = new GetDonationDashboardResponse(
            totalDeliveryLogs: count($deliveryLogs),
            totalKg: round($totalKg, 2),
            totalRations: round($totalRations, 2),
            displayUnit: $displayUnit,
            totalQuantity: round($totalQuantity, 2),
            quantityByMonth: DashboardChart::fromCounts($this->fillMonthGaps($quantityByMonth)),
            byProductType: DashboardChart::fromCounts($countByProductType),
            byRecipientOrganization: DashboardChart::fromCounts($quantityByRecipientOrganization),
            byProperty: DashboardChart::fromCounts($countByProperty),
        );

        $this->fillDonationAgreements($response, $request);
        $this->fillRecipientOrganizations($response, $request);

        return $response;
    }

    private function fillDonationAgreements(GetDonationDashboardResponse $response, GetDonationDashboardRequest $request): void
    {
        $donationAgreements = $this->donationAgreementRepository->findByFilter(new DonationAgreementFilter(
            recipientOrganizationId: $request->recipientOrganizationId,
            accountId: $request->accountId,
            limit: 0,
        ));

        $now = new \DateTimeImmutable('NOW');
        $expiringSoonLimit = $now->modify(sprintf('+%d days', DonationAgreement::EXPIRING_SOON_DAYS));

        $countByStatus = [
            self::AGREEMENT_ACTIVE => 0,
            self::AGREEMENT_EXPIRING_SOON => 0,
            self::AGREEMENT_EXPIRED => 0,
            self::AGREEMENT_NOT_STARTED => 0,
        ];

        foreach ($donationAgreements as $donationAgreement) {
            if ($donationAgreement->expirationDate() < $now) {
                ++$countByStatus[self::AGREEMENT_EXPIRED];

                continue;
            }

            if ($donationAgreement->startDate() > $now) {
                ++$countByStatus[self::AGREEMENT_NOT_STARTED];

                continue;
            }

            ++$response->activeDonationAgreements;

            if ($donationAgreement->expirationDate() <= $expiringSoonLimit) {
                ++$response->expiringSoonDonationAgreements;
                ++$countByStatus[self::AGREEMENT_EXPIRING_SOON];
            } else {
                ++$countByStatus[self::AGREEMENT_ACTIVE];
            }
        }

        $response->agreementsByStatus = DashboardChart::fromCounts($countByStatus, self::AGREEMENT_STATUS_COLORS);
    }

    private function fillRecipientOrganizations(GetDonationDashboardResponse $response, GetDonationDashboardRequest $request): void
    {
        $recipientOrganizations = $this->recipientOrganizationRepository->findByFilter(new RecipientOrganizationFilter(accountId: $request->accountId, limit: 0));

        $countByType = [];

        foreach (RecipientOrganizationType::cases() as $recipientOrganizationType) {
            $countByType[$recipientOrganizationType->value] = 0;
        }

        foreach ($recipientOrganizations as $recipientOrganization) {
            ++$countByType[$recipientOrganization->type()->value];
        }

        $response->totalRecipientOrganizations = count($recipientOrganizations);
        $response->organizationsByType = DashboardChart::fromCounts($countByType);
    }

    private function convert(float $quantity, DeliveryLogUnit $from, DeliveryLogUnit $to, float $kgPerRation): float
    {
        if ($from === $to) {
            return $quantity;
        }

        return DeliveryLogUnit::Kg === $to
            ? $quantity * $kgPerRation
            : $quantity / $kgPerRation;
    }

    private function recipientOrganizationName(RecipientOrganizationId $recipientOrganizationId): string
    {
        $key = (string) $recipientOrganizationId;

        if (!array_key_exists($key, $this->recipientOrganizations)) {
            $this->recipientOrganizations[$key] = $this->recipientOrganizationRepository->find($recipientOrganizationId);
        }

        $recipientOrganization = $this->recipientOrganizations[$key];

        return $recipientOrganization instanceof RecipientOrganization ? $recipientOrganization->name() : $key;
    }

    private function propertyName(PropertyId $propertyId): string
    {
        $key = (string) $propertyId;

        if (!array_key_exists($key, $this->properties)) {
            $this->properties[$key] = $this->propertyRepository->find($propertyId);
        }

        $property = $this->properties[$key];

        return $property instanceof Property ? $property->name() : $key;
    }

    /**
     * @param array<string, float> $quantityByMonth indexed by 'Y-m'
     *
     * @return array<string, float> indexed by 'm/Y' with empty months filled
     */
    private function fillMonthGaps(array $quantityByMonth): array
    {
        if ([] === $quantityByMonth) {
            return [];
        }

        $months = array_keys($quantityByMonth);

        $current = new \DateTimeImmutable(sprintf('%s-01', $months[0]));
        $last = new \DateTimeImmutable(sprintf('%s-01', $months[count($months) - 1]));

        $result = [];

        while ($current <= $last) {
            $result[$current->format('m/Y')] = round($quantityByMonth[$current->format('Y-m')] ?? 0.0, 2);

            $current = $current->modify('+1 month');
        }

        return $result;
    }
}
