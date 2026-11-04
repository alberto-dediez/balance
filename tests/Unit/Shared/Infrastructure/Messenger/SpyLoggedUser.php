<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Messenger;

use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;

class SpyLoggedUser extends LoggedUser
{
    private ?User $forcedUserSpy = null;

    /** @var array<int, ?User> */
    private array $forcedUserCalls = [];

    public function __construct() {}

    public function forceUser(?User $user): void
    {
        $this->forcedUserSpy = $user;
        $this->forcedUserCalls[] = $user;
    }

    public function getForcedUser(): ?User
    {
        return $this->forcedUserSpy;
    }

    /** @return array<int, ?User> */
    public function forcedUserCalls(): array
    {
        return $this->forcedUserCalls;
    }
}
