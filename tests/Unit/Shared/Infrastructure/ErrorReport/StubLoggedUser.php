<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErrorReport;

use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;

class StubLoggedUser extends LoggedUser
{
    public function __construct(
        private readonly ?User $user = null,
        private readonly ?User $impersonator = null,
    ) {}

    public function getLoggedUserOrNull(): ?User
    {
        return $this->user;
    }

    public function getImpersonatorOrNull(): ?User
    {
        return $this->impersonator;
    }

    public function isImpersonated(): bool
    {
        return $this->impersonator instanceof User;
    }
}
