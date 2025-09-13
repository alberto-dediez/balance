<?php

namespace App\Tests\Unit\Security\Domain\Model\Account;

use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;

class AccountRepositoryMother
{
    public static function inMemory(): InMemoryAccountRepository
    {
        return new InMemoryAccountRepository();
    }
}
