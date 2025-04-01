<?php

namespace App\Security\Application\Account\Remove;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Domain\Model\Account\AccountRepository;

class RemoveAccountService
{
    public function __construct(private AccountRepository $repository, private AccountAuthorizer $authorizer)
    {

    }

    public function __invoke(RemoveAccountRequest $request, User $user): RemoveAccountResponse
    {
        $account = $this->repository->find($request->accountId);

        $this->authorizer->denyUnlessCanDoThat($account, Action::Remove, $user);

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