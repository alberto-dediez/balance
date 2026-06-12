<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Dashboard;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogFilter;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionFilter;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepository;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignFilter;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
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

class GetUserDashboardService
{
    public const UNASSESSED = 'Unassessed';

    public const UNASSESSED_COLOR = 'var(--bs-secondary)';

    public function __construct(
        private readonly UserDashboardAuthorizer $authorizer,
        private readonly SurplusRepository $surplusRepository,
        private readonly DeliveryLogRepository $deliveryLogRepository,
        private readonly DoggyBagSignRepository $doggyBagSignRepository,
        private readonly DoggyBagExceptionRepository $doggyBagExceptionRepository,
        private readonly ProtocolRepository $protocolRepository,
        private readonly ProtocolAccessRepository $protocolAccessRepository,
        private readonly TrainingSessionRepository $trainingSessionRepository,
        private readonly TrainingAttendanceRepository $trainingAttendanceRepository,
    ) {}

    public function __invoke(GetUserDashboardRequest $request, User $user): GetUserDashboardResponse
    {
        $accountId = $request->accountId ?? new AccountId('00000000-0000-0000-0000-000000000000');

        $this->authorizer->denyUnlessCanDoThat($request, SecurityAction::List, $user);

        $response = new GetUserDashboardResponse();

        $this->fillSurpluses($response, $accountId, $request);
        $this->fillDeliveryLogs($response, $accountId, $request);
        $this->fillDoggyBag($response, $accountId);
        $this->fillProtocols($response, $accountId, $request);
        $this->fillTrainings($response, $accountId, $request);

        return $response;
    }

    private function fillSurpluses(GetUserDashboardResponse $response, AccountId $accountId, GetUserDashboardRequest $request): void
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

    private function fillDeliveryLogs(GetUserDashboardResponse $response, AccountId $accountId, GetUserDashboardRequest $request): void
    {
        $deliveryLogs = $this->deliveryLogRepository->findByFilter(new DeliveryLogFilter(
            accountId: $accountId,
            dateFrom: $request->dateFrom,
            dateTo: $request->dateTo,
            limit: 0,
        ));

        $displayUnit = $request->displayUnit ?? DeliveryLogUnit::Kg;
        $kgPerRation = null !== $request->kgPerRation && $request->kgPerRation > 0
            ? $request->kgPerRation
            : DeliveryLogUnit::MITECO_KG_PER_RATION;

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

    private function fillDoggyBag(GetUserDashboardResponse $response, AccountId $accountId): void
    {
        $response->totalDoggyBagSigns = count($this->doggyBagSignRepository->findByFilter(new DoggyBagSignFilter(accountId: $accountId, limit: 0)));
        $response->totalDoggyBagExceptions = count($this->doggyBagExceptionRepository->findByFilter(new DoggyBagExceptionFilter(accountId: $accountId, limit: 0)));
    }

    private function fillProtocols(GetUserDashboardResponse $response, AccountId $accountId, GetUserDashboardRequest $request): void
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

    private function fillTrainings(GetUserDashboardResponse $response, AccountId $accountId, GetUserDashboardRequest $request): void
    {
        $now = new \DateTimeImmutable('NOW');
        $trainingSessions = $this->trainingSessionRepository->findByFilter(new TrainingSessionFilter(accountId: $accountId, limit: 0));

        foreach ($trainingSessions as $trainingSession) {
            if ($this->isInRange($trainingSession->sessionDate(), $request)) {
                ++$response->totalTrainingSessions;
            }

            if ($trainingSession->sessionDate() >= $now) {
                ++$response->upcomingTrainingSessions;
            }
        }

        $trainingAttendances = $this->trainingAttendanceRepository->findByFilter(new TrainingAttendanceFilter(accountId: $accountId, limit: 0));

        foreach ($trainingAttendances as $trainingAttendance) {
            if ($this->isInRange($trainingAttendance->attendedAt(), $request)) {
                ++$response->totalTrainingAttendances;
            }
        }
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

    private function isInRange(\DateTimeImmutable $date, GetUserDashboardRequest $request): bool
    {
        if ($request->dateFrom instanceof \DateTimeImmutable && $date < $request->dateFrom) {
            return false;
        }

        return !($request->dateTo instanceof \DateTimeImmutable && $date > $request->dateTo);
    }
}
