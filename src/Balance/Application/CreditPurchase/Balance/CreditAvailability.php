<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Balance;

use App\Balance\Domain\Model\CreditPurchase\CreditBalance;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Domain\Model\Account\AccountId;

/**
 * Consumption crosses two aggregates — purchases give the tonnes, contributions take them —
 * so the real balance is composed here instead of inside either repository.
 */
final class CreditAvailability
{
    public function __construct(
        private CreditPurchaseRepository $creditPurchaseRepository,
        private ProjectContributionRepository $projectContributionRepository,
    ) {}

    public function for(AccountId $accountId, ProjectId $projectId, ?\DateTimeImmutable $now = null): CreditBalance
    {
        $now ??= new \DateTimeImmutable('NOW');
        $balance = $this->creditPurchaseRepository->balanceFor($accountId, $projectId);

        return $this->withConsumed(
            $balance,
            $this->projectContributionRepository->committedTonnes($accountId, $projectId, $now),
        );
    }

    /** @return array<CreditBalance> */
    public function forAccount(AccountId $accountId, ?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable('NOW');
        $committed = $this->projectContributionRepository->committedTonnesByAccount($accountId, $now);

        return array_map(
            fn(CreditBalance $balance): CreditBalance => $this->withConsumed(
                $balance,
                $committed[(string) $balance->projectId()] ?? 0.0,
            ),
            $this->creditPurchaseRepository->balancesByAccount($accountId),
        );
    }

    private function withConsumed(CreditBalance $balance, float $consumedTonnes): CreditBalance
    {
        return new CreditBalance(
            projectId: $balance->projectId(),
            purchasedTonnes: $balance->purchasedTonnes(),
            consumedTonnes: $consumedTonnes,
            purchasesCount: $balance->purchasesCount(),
        );
    }
}
