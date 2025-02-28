<?php

namespace App\Security\Application\ListUsers;

class ListUsersResponse
{
    public function __construct(public array $users = [], public int $totalResults = 0)
    {
        
    }
}