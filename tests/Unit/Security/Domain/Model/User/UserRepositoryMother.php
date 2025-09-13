<?php

namespace App\Tests\Unit\Security\Domain\Model\User;

use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;

class UserRepositoryMother
{
    public static function inMemory(): InMemoryUserRepository
    {
        return new InMemoryUserRepository();
    }
}
