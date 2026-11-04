<?php

declare(strict_types=1);

namespace App\Security\Application\Account;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;

final class DistributorPolicy
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
    ) {}

    public function isActiveDistributor(AccountId $accountId): bool
    {
        $account = $this->accountRepository->find($accountId);

        if (!$account instanceof Account || !$account->isDistributor()) {
            return false;
        }

        return $account->isActive() && !$account->isRemoved();
    }
}
