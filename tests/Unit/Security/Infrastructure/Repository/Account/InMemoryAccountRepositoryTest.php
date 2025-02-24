<?php

namespace App\Tests\Unit\Security\Infrastructure\Repository\Account;

use App\Security\Domain\Model\Account\AccountRepository;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryTest;
use App\Security\Infrastructure\Repository\Account\InMemoryAccountRepository;

class InMemoryAccountRepositoryTest extends AccountRepositoryTest
{
    public function createSUT(): AccountRepository
    {
        return new InMemoryAccountRepository();
    }
}