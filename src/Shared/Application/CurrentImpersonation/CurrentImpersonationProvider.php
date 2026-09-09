<?php

declare(strict_types=1);

namespace App\Shared\Application\CurrentImpersonation;

use App\Security\Domain\Model\User\User;

interface CurrentImpersonationProvider
{
    public function isImpersonating(User $user): bool;
}
