<?php

namespace App\Tests\Unit\Security\Infrastructure\Repository\User;

use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryTest;

class InMemoryUserRepositoryTest extends UserRepositoryTest
{
    public function createSUT(): UserRepository
    {
        return new InMemoryUserRepository();
    }
}
