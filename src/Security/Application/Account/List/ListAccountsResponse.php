<?php

namespace App\Security\Application\Account\List;

use App\Security\Domain\Model\Account\Account;

class ListAccountsResponse
{
    /**
     * @param Account[] $accounts
     */
    public function __construct(public array $accounts = [], public int $totalResults = 0)
    {

    }
}
