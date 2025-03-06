<?php

namespace App\Security\Application\UpdateAccount;

use InvalidArgumentException;
use App\Security\Domain\Model\Account\AccountRepository;

class UpdateAccountService
{
    public function __construct(private AccountRepository $repository)
    {

    }

    public function __invoke(UpdateAccountRequest $request): UpdateAccountResponse
    {
        $account = $this->repository->find($request->accountId);

        if (null == $account) {
            throw new InvalidArgumentException("Account not found");
        }

        if ($request->name != $account->name()) {
            $account->changeName($request->name);
        }

        if ($request->active != $account->isActive()) {
            $account->changeStatus($request->active);
        }

        $this->repository->add($account);

        return new UpdateAccountResponse(
            accountId: $account->id(),
            name: $account->name(),
            active: $account->isActive()
        );
    }
}