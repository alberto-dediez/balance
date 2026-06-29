<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Domain\Model\Provider;

use App\EventOperations\Domain\Model\Provider\ProviderRepository;

class InMemoryProviderRepositoryTest extends ProviderRepositoryTest
{
    protected function createSUT(): ProviderRepository
    {
        return ProviderRepositoryMother::inMemory();
    }
}
