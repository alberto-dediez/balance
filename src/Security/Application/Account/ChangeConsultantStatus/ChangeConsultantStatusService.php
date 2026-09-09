<?php

declare(strict_types=1);

namespace App\Security\Application\Account\ChangeConsultantStatus;

use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Application\Account\ConsultantPolicy;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ChangeConsultantStatusService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private AccountRepository $repository,
        private AccountAuthorizer $authorizer,
        private ConsultantPolicy $consultantPolicy,
    ) {}

    public function __invoke(ChangeConsultantStatusRequest $request, User $user): ChangeConsultantStatusResponse
    {
        $account = $this->repository->find($request->accountId);

        if (null == $account) {
            throw new \InvalidArgumentException('Account not found');
        }

        $this->authorizer->denyUnlessCanDoThat($account, Action::Admin, $user);

        if ($request->consultant === $account->isConsultant()) {
            return new ChangeConsultantStatusResponse(
                accountId: $account->id(),
                consultant: $account->isConsultant(),
            );
        }

        if (!$request->consultant) {
            $this->consultantPolicy->denyUnlessCanBeUnmarked($account);
        }

        $account->changeConsultantStatus($request->consultant);

        $this->repository->add($account);

        return new ChangeConsultantStatusResponse(
            accountId: $account->id(),
            consultant: $account->isConsultant(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ChangeConsultantStatusRequest;
    }
}
