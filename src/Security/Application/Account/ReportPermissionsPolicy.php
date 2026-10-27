<?php

declare(strict_types=1);

namespace App\Security\Application\Account;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountReportPermissions;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;

final class ReportPermissionsPolicy
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly ImpersonationPolicy $impersonationPolicy,
    ) {}

    public function permissionsFor(?AccountId $accountId, User $user): AccountReportPermissions
    {
        if (!$accountId instanceof AccountId) {
            return new AccountReportPermissions();
        }

        $account = $this->accountRepository->find($accountId);
        $own = $account?->reportPermissions() ?? new AccountReportPermissions();

        $userAccountId = $user->accountId();

        if (!$userAccountId instanceof AccountId || $userAccountId->equals($accountId)) {
            return $own;
        }

        if (!$account instanceof Account || !$this->impersonationPolicy->canImpersonate($account, $user)) {
            return $own;
        }

        return $this->accountRepository->find($userAccountId)?->reportPermissions() ?? $own;
    }
}
