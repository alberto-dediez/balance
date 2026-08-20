<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Application\Account\ImpersonationPolicy;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Service\ResetInterface;

class LoggedAccount implements CurrentAccountIdProvider, ResetInterface
{
    private ?Account $account = null;

    private bool $loaded = false;

    public function __construct(
        private readonly Security $security,
        private readonly AccountRepository $accountRepository,
        private readonly ImpersonatedAccount $impersonatedAccount,
        private readonly LoggedUser $loggedUser,
        private readonly ImpersonationPolicy $impersonationPolicy,
    ) {}

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $impersonated = $this->impersonatedAccount->account();

        if ($impersonated instanceof Account) {
            $user = $this->loggedUser->getLoggedUserOrNull();
            $impersonatorId = $this->impersonatedAccount->impersonatorId();

            if ($this->isImpersonationValid($impersonated, $user, $impersonatorId)) {
                $this->account = $impersonated;
                $this->loaded = true;

                return;
            }

            $this->impersonatedAccount->exit();
        }

        $user = $this->security->getUser();

        if (!$user instanceof SecurityUser || !$user->accountId() instanceof AccountId) {
            return;
        }

        $this->account = $this->accountRepository->find($user->accountId());
        $this->loaded = true;
    }

    public function __invoke(): ?Account
    {
        $this->load();

        return $this->account;
    }

    public function currentAccountId(): ?AccountId
    {
        $account = $this->__invoke();

        return $account instanceof Account ? $account->id() : null;
    }

    public function reset(): void
    {
        $this->account = null;
        $this->loaded = false;
    }

    private function isImpersonationValid(Account $impersonated, ?User $user, ?UserId $impersonatorId): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        if (!$impersonatorId instanceof UserId || !$impersonatorId->equals($user->id())) {
            return false;
        }

        return $this->impersonationPolicy->canImpersonate($impersonated, $user);
    }
}
