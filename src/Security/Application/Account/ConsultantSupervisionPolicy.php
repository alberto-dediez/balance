<?php

declare(strict_types=1);

namespace App\Security\Application\Account;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;

final class ConsultantSupervisionPolicy
{
    public function __construct(
        private readonly ?AccountRepository $accountRepository = null,
        private readonly ?UserRepository $userRepository = null,
    ) {}

    public function isActiveConsultant(AccountId $accountId): bool
    {
        $account = $this->accountRepository?->find($accountId);

        if (!$account instanceof Account || !$account->isConsultant()) {
            return false;
        }

        return $account->isActive() && !$account->isRemoved();
    }

    public function supervises(AccountId $consultantAccountId, AccountId $accountId): bool
    {
        if (!$this->isActiveConsultant($consultantAccountId)) {
            return false;
        }

        $account = $this->accountRepository?->find($accountId);

        if (!$account instanceof Account || $account->isRemoved()) {
            return false;
        }

        $assignedConsultantAccountId = $account->consultantAccountId();

        return $assignedConsultantAccountId instanceof AccountId && $assignedConsultantAccountId->equals($consultantAccountId);
    }

    /**
     * @return list<AccountId>
     */
    public function supervisedAccountIds(AccountId $consultantAccountId): array
    {
        if (!$this->accountRepository instanceof AccountRepository || !$this->isActiveConsultant($consultantAccountId)) {
            return [];
        }

        $accounts = $this->accountRepository->findByFilter(new AccountFilter(consultantAccountId: $consultantAccountId, limit: 0));

        return array_map(static fn(Account $account): AccountId => $account->id(), $accounts);
    }

    /**
     * @return list<User>
     */
    public function supervisorsOf(?AccountId $accountId): array
    {
        if (!$accountId instanceof AccountId || !$this->accountRepository instanceof AccountRepository || !$this->userRepository instanceof UserRepository) {
            return [];
        }

        $account = $this->accountRepository->find($accountId);

        if (!$account instanceof Account) {
            return [];
        }

        $consultantAccountId = $account->consultantAccountId();

        if (!$consultantAccountId instanceof AccountId || !$this->isActiveConsultant($consultantAccountId)) {
            return [];
        }

        return array_values(array_filter(
            $this->userRepository->findByAccountId($consultantAccountId),
            static fn(User $user): bool => $user->hasRole(Role::Owner) && !$user->isRemoved(),
        ));
    }
}
