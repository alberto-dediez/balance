<?php

declare(strict_types=1);

namespace App\Security\Application\User\View;

use App\Security\Domain\Model\User\User;

class ViewUserResponse
{
    public function __construct(public User $user) {}
}
