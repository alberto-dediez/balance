<?php

namespace App\Security\Application\User\List;

use App\Security\Domain\Model\User\User;

class ListUsersResponse
{
    /**
     * @param User[] $users
     */
    public function __construct(public array $users = [], public int $totalResults = 0)
    {

    }
}
