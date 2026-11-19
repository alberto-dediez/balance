<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Integration;

use App\Shared\Domain\Model\Integration\IntegrationInboxRepository;
use App\Shared\Infrastructure\Persistence\InMemory\Repository\InMemoryIntegrationInboxRepository;

class InMemoryIntegrationInboxRepositoryTest extends IntegrationInboxRepositoryTest
{
    protected function createSUT(): IntegrationInboxRepository
    {
        return new InMemoryIntegrationInboxRepository();
    }
}
