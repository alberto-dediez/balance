<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CurrentImpersonation\CurrentImpersonationProvider;

class CurrentImpersonation implements CurrentImpersonationProvider
{
    public function __construct(
        private readonly ImpersonatedAccount $impersonatedAccount,
        private readonly LoggedAccount $loggedAccount,
    ) {}

    public function isImpersonating(User $user): bool
    {
        ($this->loggedAccount)();

        if (!$this->impersonatedAccount->isImpersonating()) {
            return false;
        }

        $impersonatorId = $this->impersonatedAccount->impersonatorId();

        return $impersonatorId instanceof UserId && $impersonatorId->equals($user->id());
    }
}
