<?php

namespace App\Security\Application\Account\List;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Domain\Model\Account\AccountRepository;

class ListAccountsService
{
    public function __construct(private AccountRepository $repository, private AccountAuthorizer $authorizer)
    {

    }

    public function __invoke(ListAccountsRequest $request, User $user): ListAccountsResponse
    {
        $account = new Account(AccountId::generate());

        $this->authorizer->denyUnlessCanDoThat($account, Action::List, $user);

        $accounts = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListAccountsResponse($accounts, $totalResults);
    }
}