<?php

namespace App\Security\Application\User\ChangePassword;

use App\Security\Domain\Model\User\UserId;

class ChangePasswordRequest
{
    public function __construct(public UserId $userId, #[\SensitiveParameter] public string $password)
    {

    }
}