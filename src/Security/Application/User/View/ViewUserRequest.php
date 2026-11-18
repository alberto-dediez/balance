<?php

declare(strict_types=1);

namespace App\Security\Application\User\View;

use App\Security\Domain\Model\User\UserId;

class ViewUserRequest
{
    public function __construct(public UserId $userId) {}
}
