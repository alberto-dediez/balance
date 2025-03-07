<?php

namespace App\Security\Application\Account\List;

use App\Security\Domain\Model\Account\AccountRepository;

class ListAccountsService
{
    public function __construct(private AccountRepository $repository)
    {

    }

    public function __invoke(ListAccountsRequest $request): ListAccountsResponse
    {
        $users = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListAccountsResponse($users, $totalResults);
    }
}