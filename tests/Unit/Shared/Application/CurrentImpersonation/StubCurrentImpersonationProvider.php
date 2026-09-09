<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\CurrentImpersonation;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentImpersonation\CurrentImpersonationProvider;

class StubCurrentImpersonationProvider implements CurrentImpersonationProvider
{
    public function __construct(private readonly bool $impersonating = false) {}

    public function isImpersonating(User $user): bool
    {
        return $this->impersonating;
    }
}
