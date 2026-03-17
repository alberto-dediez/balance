<?php

namespace App\Security\Application\Account\Update;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class UpdateAccountService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(private AccountRepository $repository, private AccountAuthorizer $authorizer)
    {

    }

    public function __invoke(UpdateAccountRequest $request, User $user): UpdateAccountResponse
    {
        $account = $this->repository->find($request->accountId);

        if (null == $account) {
            throw new InvalidArgumentException("Account not found");
        }

        $this->authorizer->denyUnlessCanDoThat($account, Action::Edit, $user);

        if ($request->name !== $account->name()) {
            $account->changeName($request->name);
        }

        if ($request->active !== $account->isActive()) {
            $account->changeStatus($request->active);
        }

        $this->repository->add($account);

        return new UpdateAccountResponse(
            accountId: $account->id(),
            name: $account->name(),
            active: $account->isActive()
        );
    }
    
    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateAccountRequest;
    }
}