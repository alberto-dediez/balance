<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Dashboard;

use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointFilter;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\CriticalPoint\SupplyChainStage;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogFilter;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignFilter;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementFilter;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use App\FoodLoss\Domain\Model\Incidence\IncidenceFilter;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\FoodLoss\Domain\Model\Incidence\IncidenceType;
use App\FoodLoss\Domain\Model\Packaging\PackagingFilter;
use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;
use App\FoodLoss\Domain\Model\Packaging\PackagingType;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanFilter;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersion;
use App\FoodLoss\Domain\Model\Protocol\ProtocolFilter;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessFilter;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessRepository;
use App\FoodLoss\Domain\Model\Surplus\SurplusDestination;
use App\FoodLoss\Domain\Model\Surplus\SurplusFilter;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceFilter;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionFilter;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

class GetOwnerDashboardService
{
    public const UNASSESSED = 'Unassessed';

    public const UNASSESSED_COLOR = 'var(--bs-secondary)';

    public function __construct(
        private readonly OwnerDashboardAuthorizer $authorizer,
        private readonly CriticalPointRepository $criticalPointRepository,
        private readonly PreventionPlanRepository $preventionPlanRepository,
        private readonly SurplusRepository $surplusRepository,
        private readonly DonationAgreementRepository $donationAgreementRepository,
        private readonly DeliveryLogRepository $deliveryLogRepository,
        private readonly PackagingRepository $packagingRepository,
        private readonly DoggyBagSignRepository $doggyBagSignRepository,
        private readonly ProtocolRepository $protocolRepository,
        private readonly ProtocolAccessRepository $protocolAccessRepository,
        private readonly TrainingSessionRepository $trainingSessionRepository,
        private readonly TrainingAttendanceRepository $trainingAttendanceRepository,
        private readonly IncidenceRepository $incidenceRepository,
    ) {}

    public function __invoke(GetOwnerDashboardRequest $request, User $user): GetOwnerDashboardResponse
    {
        $accountId = $request->accountId ?? new AccountId('00000000-0000-0000-0000-000000000000');

        $this->authorizer->denyUnlessCanDoThat($request, SecurityAction::List, $user);

        $response = new GetOwnerDashboardResponse();

        $this->fillCriticalPoints($response, $accountId);
        $this->fillPreventionPlans($response, $accountId);
        $this->fillSurpluses($response, $accountId, $request);
        $this->fillDonations($response, $accountId, $request);
        $this->fillPackagings($response, $accountId);
        $this->fillDoggyBagSigns($response, $accountId);
        $this->fillProtocols($response, $accountId, $request);
        $this->fillTrainings($response, $accountId, $request);
        $this->fillIncidences($response, $accountId, $request);

        return $response;
    }

    private function fillCriticalPoints(GetOwnerDashboardResponse $response, AccountId $accountId): void
    {
        $criticalPoints = $this->criticalPointRepository->findByFilter(new CriticalPointFilter(accountId: $accountId, limit: 0));

        $countByPriority = [];

        foreach (Priority::cases() as $priority) {
            $countByPriority[$priority->value] = 0;
        }

        $countByStage = [];

        foreach (SupplyChainStage::cases() as $stage) {
            $countByStage[$stage->value] = 0;
        }

        foreach ($criticalPoints as $criticalPoint) {
            foreach ($this->assessmentsByPriority($criticalPoint) as $priorityValue => $isPossible) {
                if ($isPossible) {
                    ++$countByPriority[$priorityValue];
                }
            }

            ++$countByStage[$criticalPoint->supplyChainStage()->value];
        }

        $response->totalCriticalPoints = count($criticalPoints);
        $response->criticalPointsByPriority = DashboardChart::fromCounts($countByPriority);
        $response->criticalPointsBySupplyChainStage = DashboardChart::fromCounts($countByStage);
    }

    /**
     * @return array<string, bool> indexed by Priority value
     */
    private function assessmentsByPriority(CriticalPoint $criticalPoint): array
    {
        return [
            Priority::Prevention->value => $criticalPoint->prevention()->isPossible,
            Priority::Transformation->value => $criticalPoint->transformation()->isPossible,
            Priority::HumanDonation->value => $criticalPoint->humanDonation()->isPossible,
            Priority::AnimalFeed->value => $criticalPoint->animalFeed()->isPossible,
            Priority::Composting->value => $criticalPoint->composting()->isPossible,
        ];
    }

    private function fillPreventionPlans(GetOwnerDashboardResponse $response, AccountId $accountId): void
    {
        $preventionPlans = $this->preventionPlanRepository->findByFilter(new PreventionPlanFilter(accountId: $accountId, limit: 0));

        $countByStatus = [];
        $hasCompletedPlan = false;

        foreach ($preventionPlans as $preventionPlan) {
            $status = $preventionPlan->status()->value;
            $countByStatus[$status] = ($countByStatus[$status] ?? 0) + 1;

            if ($preventionPlan->lastCompletedVersion() instanceof PreventionPlanVersion) {
                $hasCompletedPlan = true;
            }
        }

        arsort($countByStatus);

        $response->totalPreventionPlans = count($preventionPlans);
        $response->hasCompletedPreventionPlan = $hasCompletedPlan;
        $response->preventionPlansByStatus = DashboardChart::fromCounts($countByStatus);
    }

    private function fillSurpluses(GetOwnerDashboardResponse $response, AccountId $accountId, GetOwnerDashboardRequest $request): void
    {
        $surpluses = $this->surplusRepository->findByFilter(new SurplusFilter(
            accountId: $accountId,
            dateFrom: $request->dateFrom,
            dateTo: $request->dateTo,
            limit: 0,
        ));

        $displayUnit = $request->displayUnit ?? DeliveryLogUnit::Kg;
        $kgPerRation = null !== $request->kgPerRation && $request->kgPerRation > 0
            ? $request->kgPerRation
            : DeliveryLogUnit::MITECO_KG_PER_RATION;

        $response->displayUnit = $displayUnit;

        $countByDestination = [];
        $destinationColors = [];

        foreach (SurplusDestination::cases() as $destination) {
            $countByDestination[$destination->value] = 0;
            $destinationColors[] = $destination->color();
        }

        $countByDestination[self::UNASSESSED] = 0;
        $destinationColors[] = self::UNASSESSED_COLOR;

        foreach ($surpluses as $surplus) {
            if (DeliveryLogUnit::Kg === $surplus->unit()) {
                $response->surplusKg += $surplus->quantity();
            } else {
                $response->surplusRations += $surplus->quantity();
            }

            $response->surplusTotalQuantity += $this->convert($surplus->quantity(), $surplus->unit(), $displayUnit, $kgPerRation);

            $destination = $surplus->destination();

            if (!$destination instanceof SurplusDestination) {
                ++$response->unassessedSurpluses;
                ++$countByDestination[self::UNASSESSED];

                continue;
            }

            ++$countByDestination[$destination->value];

            if (SurplusDestination::Elimination === $destination) {
                ++$response->eliminatedSurpluses;
            } else {
                ++$response->recoveredSurpluses;
            }
        }

        $response->totalSurpluses = count($surpluses);
        $response->surplusKg = round($response->surplusKg, 2);
        $response->surplusRations = round($response->surplusRations, 2);
        $response->surplusTotalQuantity = round($response->surplusTotalQuantity, 2);
        $response->surplusesByDestination = DashboardChart::fromCounts($countByDestination, $destinationColors);
    }

    private function fillDonations(GetOwnerDashboardResponse $response, AccountId $accountId, GetOwnerDashboardRequest $request): void
    {
        $donationAgreements = $this->donationAgreementRepository->findByFilter(new DonationAgreementFilter(accountId: $accountId, limit: 0));

        $now = new \DateTimeImmutable('NOW');
        $expiringSoonLimit = $now->modify(sprintf('+%d days', DonationAgreement::EXPIRING_SOON_DAYS));

        foreach ($donationAgreements as $donationAgreement) {
            if ($donationAgreement->expirationDate() < $now) {
                ++$response->expiredDonationAgreements;

                continue;
            }

            if ($donationAgreement->startDate() > $now) {
                continue;
            }

            ++$response->activeDonationAgreements;

            if ($donationAgreement->expirationDate() <= $expiringSoonLimit) {
                ++$response->expiringSoonDonationAgreements;
            }
        }

        $displayUnit = $request->displayUnit ?? DeliveryLogUnit::Kg;
        $kgPerRation = null !== $request->kgPerRation && $request->kgPerRation > 0
            ? $request->kgPerRation
            : DeliveryLogUnit::MITECO_KG_PER_RATION;

        $deliveryLogs = $this->deliveryLogRepository->findByFilter(new DeliveryLogFilter(
            accountId: $accountId,
            dateFrom: $request->dateFrom,
            dateTo: $request->dateTo,
            limit: 0,
        ));

        foreach ($deliveryLogs as $deliveryLog) {
            if (DeliveryLogUnit::Kg === $deliveryLog->unit()) {
                $response->deliveredKg += $deliveryLog->quantity();
            } else {
                $response->deliveredRations += $deliveryLog->quantity();
            }

            $response->deliveredTotalQuantity += $this->convert($deliveryLog->quantity(), $deliveryLog->unit(), $displayUnit, $kgPerRation);
        }

        $response->totalDeliveryLogs = count($deliveryLogs);
        $response->deliveredKg = round($response->deliveredKg, 2);
        $response->deliveredRations = round($response->deliveredRations, 2);
        $response->deliveredTotalQuantity = round($response->deliveredTotalQuantity, 2);
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

    private function fillPackagings(GetOwnerDashboardResponse $response, AccountId $accountId): void
    {
        $packagings = $this->packagingRepository->findByFilter(new PackagingFilter(accountId: $accountId, limit: 0));

        $stockByType = [];

        foreach (PackagingType::cases() as $packagingType) {
            $stockByType[$packagingType->value] = 0;
        }

        foreach ($packagings as $packaging) {
            foreach ($packaging->stocks() as $stock) {
                $stockByType[$packaging->packagingType()->value] += $stock->stock();
                $response->totalPackagingStock += $stock->stock();
            }
        }

        $response->totalPackagings = count($packagings);
        $response->packagingStockByType = DashboardChart::fromCounts($stockByType);
    }

    private function fillDoggyBagSigns(GetOwnerDashboardResponse $response, AccountId $accountId): void
    {
        $response->totalDoggyBagSigns = count($this->doggyBagSignRepository->findByFilter(new DoggyBagSignFilter(accountId: $accountId, limit: 0)));
    }

    private function fillProtocols(GetOwnerDashboardResponse $response, AccountId $accountId, GetOwnerDashboardRequest $request): void
    {
        $protocols = $this->protocolRepository->findByFilter(new ProtocolFilter(accountId: $accountId, limit: 0));
        $protocolAccesses = $this->protocolAccessRepository->findByFilter(new ProtocolAccessFilter(accountId: $accountId, limit: 0));

        $accessedProtocolIds = [];

        foreach ($protocolAccesses as $protocolAccess) {
            $accessedProtocolIds[(string) $protocolAccess->protocolId()] = true;

            if ($this->isInRange($protocolAccess->accessedAt(), $request)) {
                ++$response->totalProtocolAccesses;
            }
        }

        foreach ($protocols as $protocol) {
            if (array_key_exists((string) $protocol->id(), $accessedProtocolIds)) {
                ++$response->readProtocols;
            }
        }

        $response->totalProtocols = count($protocols);
    }

    private function fillTrainings(GetOwnerDashboardResponse $response, AccountId $accountId, GetOwnerDashboardRequest $request): void
    {
        $trainingSessions = $this->trainingSessionRepository->findByFilter(new TrainingSessionFilter(accountId: $accountId, limit: 0));

        foreach ($trainingSessions as $trainingSession) {
            if ($this->isInRange($trainingSession->sessionDate(), $request)) {
                ++$response->totalTrainingSessions;
            }
        }

        $trainingAttendances = $this->trainingAttendanceRepository->findByFilter(new TrainingAttendanceFilter(accountId: $accountId, limit: 0));

        foreach ($trainingAttendances as $trainingAttendance) {
            if ($this->isInRange($trainingAttendance->attendedAt(), $request)) {
                ++$response->totalTrainingAttendances;
            }
        }
    }

    private function fillIncidences(GetOwnerDashboardResponse $response, AccountId $accountId, GetOwnerDashboardRequest $request): void
    {
        $incidences = $this->incidenceRepository->findByFilter(new IncidenceFilter(accountId: $accountId, limit: 0));

        $countByType = [];

        foreach (IncidenceType::cases() as $incidenceType) {
            $countByType[$incidenceType->value] = 0;
        }

        foreach ($incidences as $incidence) {
            if (!$this->isInRange($incidence->exceptionOccurredOn(), $request)) {
                continue;
            }

            ++$response->totalIncidences;
            ++$countByType[$incidence->incidenceType()->value];
        }

        $response->incidencesByType = DashboardChart::fromCounts($countByType);
    }

    private function isInRange(\DateTimeImmutable $date, GetOwnerDashboardRequest $request): bool
    {
        if ($request->dateFrom instanceof \DateTimeImmutable && $date < $request->dateFrom) {
            return false;
        }

        return !($request->dateTo instanceof \DateTimeImmutable && $date > $request->dateTo);
    }
}
