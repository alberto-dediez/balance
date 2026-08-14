<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Balance;

use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Domain\Model\CreditPurchase\CreditBalance;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseFilter;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

class ListAccountCreditsService
{
    public function __construct(
        private CreditAvailability $creditAvailability,
        private CreditPurchaseRepository $creditPurchaseRepository,
        private ProjectContributionRepository $projectContributionRepository,
        private CreditPurchaseAuthorizer $authorizer,
    ) {}

    public function __invoke(ListAccountCreditsRequest $request, User $user): ListAccountCreditsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new CreditPurchase(id: CreditPurchaseId::generate(), accountId: $request->accountId),
            SecurityAction::List,
            $user,
        );

        $purchasesByProject = $this->purchasesByProject($request->accountId);

        return new ListAccountCreditsResponse(
            balances: $this->withEmptyBalances(
                $this->creditAvailability->forAccount($request->accountId),
                $purchasesByProject,
            ),
            purchasesByProject: $purchasesByProject,
            allocationsByPurchase: $this->projectContributionRepository->findAllocationsByAccount($request->accountId),
        );
    }

    /**
     * @param array<CreditBalance>                $balances
     * @param array<string, list<CreditPurchase>> $purchasesByProject
     *
     * @return list<CreditBalance>
     */
    private function withEmptyBalances(array $balances, array $purchasesByProject): array
    {
        $balances = array_values($balances);

        $projectIds = array_map(
            static fn(CreditBalance $balance): string => (string) $balance->projectId(),
            $balances,
        );

        foreach ($purchasesByProject as $projectId => $purchases) {
            if (in_array($projectId, $projectIds, true)) {
                continue;
            }

            $purchaseProjectId = $purchases[0]->projectId();

            if ($purchaseProjectId instanceof ProjectId) {
                $balances[] = new CreditBalance(
                    projectId: $purchaseProjectId,
                    purchasedTonnes: 0.0,
                    consumedTonnes: 0.0,
                    purchasesCount: 0,
                );
            }
        }

        return $balances;
    }

    /** @return array<string, list<CreditPurchase>> */
    private function purchasesByProject(AccountId $accountId): array
    {
        $purchases = $this->creditPurchaseRepository->findByFilter(new CreditPurchaseFilter(
            accountId: $accountId,
            limit: 0,
        ));

        $purchases = array_values($purchases);

        usort(
            $purchases,
            static fn(CreditPurchase $a, CreditPurchase $b): int => $b->requestedAt() <=> $a->requestedAt(),
        );

        $purchasesByProject = [];

        foreach ($purchases as $purchase) {
            $projectId = $purchase->projectId();

            if ($projectId instanceof ProjectId) {
                $purchasesByProject[(string) $projectId][] = $purchase;
            }
        }

        return $purchasesByProject;
    }
}
