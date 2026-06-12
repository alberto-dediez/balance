<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingSession\Dashboard;

use App\FoodLoss\Application\Dashboard\DashboardChart;
use App\FoodLoss\Application\Protocol\ProtocolAuthorizer;
use App\FoodLoss\Application\TrainingSession\TrainingSessionAuthorizer;
use App\FoodLoss\Domain\Model\Protocol\Protocol;
use App\FoodLoss\Domain\Model\Protocol\ProtocolFilter;
use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessFilter;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessRepository;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceFilter;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSession;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class GetTrainingDashboardService
{
    /** @var array<string, TrainingSession|null> */
    private array $trainingSessions = [];

    /** @var array<string, Protocol|null> */
    private array $protocols = [];

    public function __construct(
        private readonly TrainingSessionRepository $repository,
        private readonly TrainingSessionAuthorizer $authorizer,
        private readonly ProtocolAuthorizer $protocolAuthorizer,
        private readonly TrainingAttendanceRepository $trainingAttendanceRepository,
        private readonly ProtocolRepository $protocolRepository,
        private readonly ProtocolAccessRepository $protocolAccessRepository,
    ) {}

    public function __invoke(GetTrainingDashboardRequest $request, User $user): GetTrainingDashboardResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new TrainingSession(id: TrainingSessionId::generate(), accountId: $request->accountId), SecurityAction::List, $user);
        $this->protocolAuthorizer->denyUnlessCanDoThat(new Protocol(id: ProtocolId::generate(), accountId: $request->accountId), SecurityAction::List, $user);

        $request->limit = 0;
        $request->page = 0;

        $response = new GetTrainingDashboardResponse();

        $this->fillTrainingSessions($response, $request);
        $this->fillTrainingAttendances($response, $request);
        $this->fillProtocols($response, $request);

        return $response;
    }

    private function fillTrainingSessions(GetTrainingDashboardResponse $response, GetTrainingDashboardRequest $request): void
    {
        $trainingSessions = $this->repository->findByFilter($request);

        $countByMonth = [];

        foreach ($trainingSessions as $trainingSession) {
            if (!$this->isInRange($trainingSession->sessionDate(), $request)) {
                continue;
            }

            ++$response->totalTrainingSessions;

            $month = $trainingSession->sessionDate()->format('Y-m');
            $countByMonth[$month] = ($countByMonth[$month] ?? 0) + 1;
        }

        ksort($countByMonth);

        $response->sessionsByMonth = DashboardChart::fromCounts($this->fillMonthGaps($countByMonth));
    }

    private function fillTrainingAttendances(GetTrainingDashboardResponse $response, GetTrainingDashboardRequest $request): void
    {
        $trainingAttendances = $this->trainingAttendanceRepository->findByFilter(new TrainingAttendanceFilter(accountId: $request->accountId, limit: 0));

        $countBySession = [];

        foreach ($trainingAttendances as $trainingAttendance) {
            if (!$this->isInRange($trainingAttendance->attendedAt(), $request)) {
                continue;
            }

            ++$response->totalTrainingAttendances;

            if (!$trainingAttendance->trainingSessionId() instanceof TrainingSessionId) {
                continue;
            }

            $trainingSessionTitle = $this->trainingSessionTitle($trainingAttendance->trainingSessionId());
            $countBySession[$trainingSessionTitle] = ($countBySession[$trainingSessionTitle] ?? 0) + 1;
        }

        arsort($countBySession);

        $response->attendancesBySession = DashboardChart::fromCounts($countBySession);
    }

    private function fillProtocols(GetTrainingDashboardResponse $response, GetTrainingDashboardRequest $request): void
    {
        $protocols = $this->protocolRepository->findByFilter(new ProtocolFilter(accountId: $request->accountId, limit: 0));
        $protocolAccesses = $this->protocolAccessRepository->findByFilter(new ProtocolAccessFilter(accountId: $request->accountId, limit: 0));

        $accessedProtocolIds = [];
        $countByProtocol = [];
        $countByMonth = [];

        foreach ($protocolAccesses as $protocolAccess) {
            if ($protocolAccess->protocolId() instanceof ProtocolId) {
                $accessedProtocolIds[(string) $protocolAccess->protocolId()] = true;
            }

            if (!$this->isInRange($protocolAccess->accessedAt(), $request)) {
                continue;
            }

            ++$response->totalProtocolAccesses;

            $month = $protocolAccess->accessedAt()->format('Y-m');
            $countByMonth[$month] = ($countByMonth[$month] ?? 0) + 1;

            if (!$protocolAccess->protocolId() instanceof ProtocolId) {
                continue;
            }

            $protocolName = $this->protocolName($protocolAccess->protocolId());
            $countByProtocol[$protocolName] = ($countByProtocol[$protocolName] ?? 0) + 1;
        }

        foreach ($protocols as $protocol) {
            if (array_key_exists((string) $protocol->id(), $accessedProtocolIds)) {
                ++$response->readProtocols;
            }
        }

        arsort($countByProtocol);
        ksort($countByMonth);

        $response->totalProtocols = count($protocols);
        $response->accessesByProtocol = DashboardChart::fromCounts($countByProtocol);
        $response->accessesByMonth = DashboardChart::fromCounts($this->fillMonthGaps($countByMonth));
    }

    private function trainingSessionTitle(TrainingSessionId $trainingSessionId): string
    {
        $key = (string) $trainingSessionId;

        if (!array_key_exists($key, $this->trainingSessions)) {
            $this->trainingSessions[$key] = $this->repository->find($trainingSessionId);
        }

        $trainingSession = $this->trainingSessions[$key];

        return $trainingSession instanceof TrainingSession ? $trainingSession->title() : $key;
    }

    private function protocolName(ProtocolId $protocolId): string
    {
        $key = (string) $protocolId;

        if (!array_key_exists($key, $this->protocols)) {
            $this->protocols[$key] = $this->protocolRepository->find($protocolId);
        }

        $protocol = $this->protocols[$key];

        return $protocol instanceof Protocol ? $protocol->name() : $key;
    }

    private function isInRange(\DateTimeImmutable $date, GetTrainingDashboardRequest $request): bool
    {
        if ($request->dateFrom instanceof \DateTimeImmutable && $date < $request->dateFrom) {
            return false;
        }

        return !($request->dateTo instanceof \DateTimeImmutable && $date > $request->dateTo);
    }

    /**
     * @param array<string, int> $countByMonth indexed by 'Y-m'
     *
     * @return array<string, int> indexed by 'm/Y' with empty months filled
     */
    private function fillMonthGaps(array $countByMonth): array
    {
        if ([] === $countByMonth) {
            return [];
        }

        $months = array_keys($countByMonth);

        $current = new \DateTimeImmutable(sprintf('%s-01', $months[0]));
        $last = new \DateTimeImmutable(sprintf('%s-01', $months[count($months) - 1]));

        $result = [];

        while ($current <= $last) {
            $result[$current->format('m/Y')] = $countByMonth[$current->format('Y-m')] ?? 0;

            $current = $current->modify('+1 month');
        }

        return $result;
    }
}
