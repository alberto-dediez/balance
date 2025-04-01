<?php

namespace App\Tests\Unit\Security\Application\User;

use App\Security\Application\User\UserAuthorizer;

class UserAuthorizerMother
{
    public static function create(): UserAuthorizer
    {
        return new UserAuthorizer();
    }
}