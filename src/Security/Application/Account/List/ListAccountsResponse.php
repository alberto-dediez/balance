<?php

namespace App\Security\Application\Account\List;

class ListAccountsResponse
{
    public function __construct(public array $accounts = [], public int $totalResults = 0)
    {
        
    }
}