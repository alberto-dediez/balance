<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Persistence\InMemory\Repository;

use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryTest;

class InMemoryAccountRepositoryTest extends AccountRepositoryTest
{
    public function createSUT(): AccountRepository
    {
        return new InMemoryAccountRepository();
    }
}
