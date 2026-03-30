<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account;

use App\Security\Application\Account\AccountAuthorizer;

class AccountAuthorizerMother
{
    public static function create(): AccountAuthorizer
    {
        return new AccountAuthorizer();
    }
}
