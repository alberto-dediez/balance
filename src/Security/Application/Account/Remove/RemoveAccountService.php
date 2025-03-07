<?php

namespace App\Security\Application\Account\Remove;

use App\Security\Domain\Model\Account\AccountRepository;
use InvalidArgumentException;

class RemoveAccountService
{
    public function __construct(private AccountRepository $repository)
    {

    }

    public function __invoke(RemoveAccountRequest $request): RemoveAccountResponse
    {
        $account = $this->repository->find($request->accountId);

        if (null == $account) {
            throw new InvalidArgumentException("Account not found");
        }

        $account->remove();

        $this->repository->add($account);

        return new RemoveAccountResponse(
            accountId: $account->id()
        );
    }
}