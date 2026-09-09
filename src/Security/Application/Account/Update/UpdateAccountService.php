<?php

declare(strict_types=1);

namespace App\Security\Application\Account\Update;

use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Application\Account\ConsultantPolicy;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateAccountService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private AccountRepository $repository,
        private AccountAuthorizer $authorizer,
        private ConsultantPolicy $consultantPolicy,
    ) {}

    public function __invoke(UpdateAccountRequest $request, User $user): UpdateAccountResponse
    {
        $account = $this->repository->find($request->accountId);

        if (null == $account) {
            throw new \InvalidArgumentException('Account not found');
        }

        $this->authorizer->denyUnlessCanDoThat($account, Action::Edit, $user);

        if ($request->name !== $account->name()) {
            $account->changeName($request->name);
        }

        if ($request->active !== $account->isActive()) {
            $account->changeStatus($request->active);
        }

        if (null !== $request->emissionFactorGroupId
            && (null === $account->emissionFactorGroupId() || !$request->emissionFactorGroupId->equals($account->emissionFactorGroupId()))
        ) {
            $account->changeEmissionFactorGroup($request->emissionFactorGroupId);
        }

        if ($request->consultantAccountId instanceof AccountId) {
            $this->updateConsultantStatus($account, $request, $user);
            $this->updateConsultant($account, $request, $user);
        } else {
            $this->updateConsultant($account, $request, $user);
            $this->updateConsultantStatus($account, $request, $user);
        }

        $this->repository->add($account);

        return new UpdateAccountResponse(
            accountId: $account->id(),
            name: $account->name(),
            active: $account->isActive(),
        );
    }

    private function updateConsultantStatus(Account $account, UpdateAccountRequest $request, User $user): void
    {
        if ($request->consultant === $account->isConsultant()) {
            return;
        }

        $this->authorizer->denyUnlessCanDoThat($account, Action::Admin, $user);

        if (!$request->consultant) {
            $this->consultantPolicy->denyUnlessCanBeUnmarked($account);
        }

        $account->changeConsultantStatus($request->consultant);
    }

    private function updateConsultant(Account $account, UpdateAccountRequest $request, User $user): void
    {
        if ($this->sameConsultant($request->consultantAccountId, $account->consultantAccountId())) {
            return;
        }

        $this->authorizer->denyUnlessCanDoThat($account, Action::Admin, $user);
        $this->consultantPolicy->denyUnlessAssignable($request->consultantAccountId);

        $account->changeConsultant($request->consultantAccountId);
    }

    private function sameConsultant(?AccountId $requested, ?AccountId $current): bool
    {
        if (!$requested instanceof AccountId || !$current instanceof AccountId) {
            return !$requested instanceof AccountId && !$current instanceof AccountId;
        }

        return $requested->equals($current);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateAccountRequest;
    }
}
