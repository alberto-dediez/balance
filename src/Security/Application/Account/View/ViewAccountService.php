<?php

declare(strict_types=1);

namespace App\Security\Application\Account\View;

use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;

class ViewAccountService
{
    public function __construct(private readonly AccountRepository $repository, private readonly AccountAuthorizer $authorizer) {}

    public function __invoke(ViewAccountRequest $request, User $user): ViewAccountResponse
    {
        $account = $this->repository->find($request->accountId);

        if (null === $account) {
            throw new \InvalidArgumentException('Account not found');
        }

        $this->authorizer->denyUnlessCanDoThat($account, Action::View, $user);

        return new ViewAccountResponse($account);
    }
}
