<?php

namespace App\Tests\Unit\Security\Domain\Model\User;

use App\Security\Infrastructure\Repository\User\InMemoryUserRepository;

class UserRepositoryMother
{
    public static function inMemory(): InMemoryUserRepository
    {
        return new InMemoryUserRepository();
    }
}