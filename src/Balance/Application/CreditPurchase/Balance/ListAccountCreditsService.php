<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Balance;

use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListAccountCreditsService
{
    public function __construct(
        private CreditPurchaseRepository $repository,
        private CreditPurchaseAuthorizer $authorizer,
    ) {}

    public function __invoke(ListAccountCreditsRequest $request, User $user): ListAccountCreditsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new CreditPurchase(id: CreditPurchaseId::generate(), accountId: $request->accountId),
            SecurityAction::List,
            $user,
        );

        return new ListAccountCreditsResponse($this->repository->balancesByAccount($request->accountId));
    }
}
