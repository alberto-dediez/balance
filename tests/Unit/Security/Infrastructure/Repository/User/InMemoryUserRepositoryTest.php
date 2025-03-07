<?php

namespace App\Tests\Unit\Security\Infrastructure\Repository\User;

use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Repository\Account\InMemoryAccountRepository;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryTest;
use App\Security\Infrastructure\Repository\User\InMemoryUserRepository;

class InMemoryUserRepositoryTest extends UserRepositoryTest
{
    public function createSUT(): UserRepository
    {
        return new InMemoryUserRepository();
    }
}