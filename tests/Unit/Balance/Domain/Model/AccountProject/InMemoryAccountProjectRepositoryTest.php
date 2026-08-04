<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\AccountProject;

use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;

class InMemoryAccountProjectRepositoryTest extends AccountProjectRepositoryTest
{
    protected function createSUT(): AccountProjectRepository
    {
        return AccountProjectRepositoryMother::inMemory();
    }
}
