<?php

declare(strict_types=1);

namespace App\Security\Application\Account\AssignConsultant;

use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Application\Account\ConsultantPolicy;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class AssignConsultantService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private AccountRepository $repository,
        private AccountAuthorizer $authorizer,
        private ConsultantPolicy $consultantPolicy,
    ) {}

    public function __invoke(AssignConsultantRequest $request, User $user): AssignConsultantResponse
    {
        $account = $this->repository->find($request->accountId);

        if (null == $account) {
            throw new \InvalidArgumentException('Account not found');
        }

        $this->authorizer->denyUnlessCanDoThat($account, Action::Admin, $user);

        $this->consultantPolicy->denyUnlessAssignable($request->consultantAccountId);

        $account->changeConsultant($request->consultantAccountId);

        $this->repository->add($account);

        return new AssignConsultantResponse(
            accountId: $account->id(),
            consultantAccountId: $account->consultantAccountId(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof AssignConsultantRequest;
    }
}
