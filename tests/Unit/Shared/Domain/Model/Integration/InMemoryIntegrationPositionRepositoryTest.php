<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Integration;

use App\Shared\Domain\Model\Integration\IntegrationPositionRepository;
use App\Shared\Infrastructure\Persistence\InMemory\Repository\InMemoryIntegrationPositionRepository;

class InMemoryIntegrationPositionRepositoryTest extends IntegrationPositionRepositoryTest
{
    protected function createSUT(): IntegrationPositionRepository
    {
        return new InMemoryIntegrationPositionRepository();
    }
}
