<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User;

use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;

class UserAuthorizerMother
{
    public static function create(): UserAuthorizer
    {
        return new UserAuthorizer();
    }

    public static function withCurrentAccount(AccountId $currentAccountId): UserAuthorizer
    {
        return new UserAuthorizer(new StubCurrentAccountIdProvider($currentAccountId));
    }
}
