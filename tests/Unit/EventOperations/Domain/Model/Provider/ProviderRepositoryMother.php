<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Domain\Model\Provider;

use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderRepository;

class ProviderRepositoryMother
{
    public static function inMemory(): InMemoryProviderRepository
    {
        return new InMemoryProviderRepository();
    }
}
