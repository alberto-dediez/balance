<?php

namespace App\Tests\Unit\Security\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;

class FakeLoggedUser extends LoggedUser
{
    public function __construct(private readonly ?User $user = null)
    {

    }

    public function __invoke(): User
    {
        assert($this->user instanceof User);

        return $this->user;
    }
}
