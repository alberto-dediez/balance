<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Alerts;

use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Application\CreditPurchase\Balance\CreditReservationLock;
use App\Balance\Domain\Model\CreditPurchase\CreditAlertThreshold;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

final class CheckCreditAlertsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CreditPurchaseRepository $creditPurchaseRepository,
        private ProjectContributionRepository $projectContributionRepository,
        private CreditAvailability $creditAvailability,
        private CreditReservationLock $creditReservationLock,
    ) {}

    public function __invoke(CheckCreditAlertsRequest $request, User $user): CheckCreditAlertsResponse
    {
        $evaluated = 0;
        $alerted = 0;

        foreach ($this->scopesToEvaluate($request) as [$accountId, $projectId]) {
            /** @var ?bool $outcome */
            $outcome = $this->creditReservationLock->withLock(
                $accountId,
                $projectId,
                fn(): ?bool => $this->evaluate($accountId, $projectId, $request->now),
            );

            if (null === $outcome) {
                continue;
            }

            ++$evaluated;

            if ($outcome) {
                ++$alerted;
            }
        }

        return new CheckCreditAlertsResponse(evaluated: $evaluated, alerted: $alerted);
    }

    /** @return list<array{AccountId, ProjectId}> */
    private function scopesToEvaluate(CheckCreditAlertsRequest $request): array
    {
        if ($request->accountId instanceof AccountId && $request->projectId instanceof ProjectId) {
            return [[$request->accountId, $request->projectId]];
        }

        $scopes = [];

        foreach ($this->creditPurchaseRepository->latestValidatedPurchases() as $creditPurchase) {
            $accountId = $creditPurchase->accountId();
            $projectId = $creditPurchase->projectId();

            if (!$accountId instanceof AccountId || !$projectId instanceof ProjectId) {
                continue;
            }

            if ($request->accountId instanceof AccountId && !$request->accountId->equals($accountId)) {
                continue;
            }

            if ($request->projectId instanceof ProjectId && !$request->projectId->equals($projectId)) {
                continue;
            }

            $scopes[] = [$accountId, $projectId];
        }

        return $scopes;
    }

    private function evaluate(AccountId $accountId, ProjectId $projectId, \DateTimeImmutable $now): ?bool
    {
        $creditPurchase = $this->creditPurchaseRepository->latestValidatedFor($accountId, $projectId);

        if (!$creditPurchase instanceof CreditPurchase) {
            return null;
        }

        $purchasedTonnes = $creditPurchase->purchasedTonnes();

        if (null === $purchasedTonnes || $purchasedTonnes <= 0.0) {
            return null;
        }

        $allocated = $this->projectContributionRepository->committedAllocatedTonnesByPurchase($accountId, $projectId, $now);
        $consumedTonnes = $allocated[(string) $creditPurchase->id()] ?? 0.0;

        $threshold = CreditAlertThreshold::highestReachedBy($consumedTonnes, $purchasedTonnes);

        if (null === $threshold || $creditPurchase->hasNotified($threshold)) {
            return false;
        }

        $creditPurchase->reachThreshold(
            threshold: $threshold,
            consumedTonnes: $consumedTonnes,
            availableTonnes: max(0.0, $purchasedTonnes - $consumedTonnes),
            projectAvailableTonnes: $this->creditAvailability->for($accountId, $projectId, $now)->availableTonnes(),
        );

        $this->creditPurchaseRepository->add($creditPurchase);

        return true;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CheckCreditAlertsRequest;
    }
}
