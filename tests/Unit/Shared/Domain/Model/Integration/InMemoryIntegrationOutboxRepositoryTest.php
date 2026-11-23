<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Integration;

use App\Shared\Domain\Model\Integration\IntegrationOutboxRepository;
use App\Shared\Infrastructure\Persistence\InMemory\Repository\InMemoryIntegrationOutboxRepository;

class InMemoryIntegrationOutboxRepositoryTest extends IntegrationOutboxRepositoryTest
{
    protected function createSUT(): IntegrationOutboxRepository
    {
        return new InMemoryIntegrationOutboxRepository();
    }
}
