<?php

namespace App\Security\Application\User\List;

class ListUsersResponse
{
    public function __construct(public array $users = [], public int $totalResults = 0)
    {
        
    }
}