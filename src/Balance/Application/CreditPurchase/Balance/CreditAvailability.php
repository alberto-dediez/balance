<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Balance;

use App\Balance\Domain\Model\CreditPurchase\CreditBalance;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseFilter;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseStatus;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Domain\Model\Account\AccountId;

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

    public function ownFor(AccountId $accountId, ProjectId $projectId, ?\DateTimeImmutable $now = null): float
    {
        return $this->availableByPaymentType($accountId, $projectId, CreditPurchasePaymentType::PaidByClient, 0.0, $now);
    }

    public function sharedFor(AccountId $accountId, ProjectId $projectId, ?\DateTimeImmutable $now = null): float
    {
        $now ??= new \DateTimeImmutable('NOW');

        return $this->availableByPaymentType(
            $accountId,
            $projectId,
            CreditPurchasePaymentType::PaidByBioscore,
            $this->projectContributionRepository->unallocatedSharedTonnes($accountId, $projectId, $now),
            $now,
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

    private function availableByPaymentType(
        AccountId $accountId,
        ProjectId $projectId,
        CreditPurchasePaymentType $paymentType,
        float $committedWithoutLotTonnes,
        ?\DateTimeImmutable $now,
    ): float {
        $purchases = $this->creditPurchaseRepository->findByFilter(new CreditPurchaseFilter(
            accountId: $accountId,
            projectId: $projectId,
            status: CreditPurchaseStatus::Validated,
            paymentType: $paymentType,
            limit: 0,
        ));

        $allocated = $this->projectContributionRepository->allocatedTonnesByPurchase($accountId, $projectId);

        $purchased = 0.0;
        $allocatedToPurchases = 0.0;

        foreach ($purchases as $creditPurchase) {
            $purchased += $creditPurchase->availableTonnes();
            $allocatedToPurchases += $allocated[(string) $creditPurchase->id()] ?? 0.0;
        }

        return max(0.0, min(
            $purchased - $allocatedToPurchases - $committedWithoutLotTonnes,
            $this->for($accountId, $projectId, $now)->availableTonnes(),
        ));
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
