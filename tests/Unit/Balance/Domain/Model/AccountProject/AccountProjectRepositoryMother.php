<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\AccountProject;

use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountProjectRepository;

class AccountProjectRepositoryMother
{
    public static function inMemory(): InMemoryAccountProjectRepository
    {
        return new InMemoryAccountProjectRepository();
    }
}
