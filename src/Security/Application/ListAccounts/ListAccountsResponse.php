<?php

namespace App\Security\Application\ListAccounts;

class ListAccountsResponse
{
    public function __construct(public array $accounts = [], public int $totalResults = 0)
    {
        
    }
}