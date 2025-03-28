<?php

namespace App\Tests\Unit\Security\Domain\Model\Account;

use App\Security\Infrastructure\Repository\Account\InMemoryAccountRepository;

class AccountRepositoryMother
{
    public static function inMemory(): InMemoryAccountRepository
    {
        return new InMemoryAccountRepository();
    }
}