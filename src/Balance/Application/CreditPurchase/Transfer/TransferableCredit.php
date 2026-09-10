<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Transfer;

use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Domain\Model\Account\AccountId;

final class TransferableCredit
{
    public function __construct(
        private ProjectContributionRepository $projectContributionRepository,
        private CreditAvailability $creditAvailability,
    ) {}

    public function forPurchase(CreditPurchase $creditPurchase): float
    {
        $accountId = $creditPurchase->accountId();
        $projectId = $creditPurchase->projectId();

        if (!$accountId instanceof AccountId || !$projectId instanceof ProjectId) {
            return 0.0;
        }

        $allocated = $this->projectContributionRepository->allocatedTonnesByPurchase($accountId, $projectId);
        $balance = $this->creditAvailability->for($accountId, $projectId);

        return max(0.0, min(
            $creditPurchase->availableTonnes() - ($allocated[(string) $creditPurchase->id()] ?? 0.0),
            max(0.0, $balance->purchasedTonnes() - $balance->consumedTonnes()),
        ));
    }
}
