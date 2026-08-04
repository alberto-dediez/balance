<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\List;

use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListCreditPurchasesService
{
    public function __construct(
        private CreditPurchaseRepository $repository,
        private CreditPurchaseAuthorizer $authorizer,
    ) {}

    public function __invoke(ListCreditPurchasesRequest $request, User $user): ListCreditPurchasesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new CreditPurchase(id: CreditPurchaseId::generate(), accountId: $request->accountId),
            SecurityAction::List,
            $user,
        );

        $creditPurchases = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListCreditPurchasesResponse($creditPurchases, $totalResults);
    }
}
