<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Allocations;

use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionCreditAllocation;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListCreditPurchaseAllocationsService
{
    public function __construct(
        private CreditPurchaseRepository $creditPurchaseRepository,
        private ProjectContributionRepository $projectContributionRepository,
        private CreditPurchaseAuthorizer $authorizer,
    ) {}

    public function __invoke(ListCreditPurchaseAllocationsRequest $request, User $user): ListCreditPurchaseAllocationsResponse
    {
        $creditPurchase = $this->creditPurchaseRepository->find($request->creditPurchaseId);

        if (null === $creditPurchase) {
            throw new \InvalidArgumentException('Credit purchase not found');
        }

        $this->authorizer->denyUnlessCanDoThat($creditPurchase, SecurityAction::View, $user);

        $allocations = $this->projectContributionRepository->findAllocationsByCreditPurchase($creditPurchase->id());

        $allocatedTonnes = array_sum(
            array_map(static fn(ProjectContributionCreditAllocation $allocation): float => $allocation->tonnes(), $allocations),
        );

        return new ListCreditPurchaseAllocationsResponse(
            creditPurchase: $creditPurchase,
            allocations: $allocations,
            allocatedTonnes: $allocatedTonnes,
            availableTonnes: max(0.0, $creditPurchase->availableTonnes() - $allocatedTonnes),
        );
    }
}
