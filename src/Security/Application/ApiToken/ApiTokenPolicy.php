<?php

declare(strict_types=1);

namespace App\Security\Application\ApiToken;

use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\ApiToken\ApiToken;
use App\Security\Domain\Model\User\User;

class ApiTokenPolicy
{
    public function __construct(private AccountRepository $accountRepository) {}

    public function userCanHaveTokens(User $user): bool
    {
        return null === $user->accountId() || true === $user->account()?->allowsApiTokens();
    }

    public function isUsable(ApiToken $token, \DateTimeImmutable $now): bool
    {
        if (!$token->isActive($now)) {
            return false;
        }

        $accountId = $token->accountId();

        if (null === $accountId) {
            return true;
        }

        return true === $this->accountRepository->find($accountId)?->allowsApiTokens();
    }
}
