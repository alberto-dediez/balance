<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\AlertCheckQueue;

use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueRepository;

class InMemoryAlertCheckQueueRepositoryTest extends AlertCheckQueueRepositoryTest
{
    protected function createSUT(): AlertCheckQueueRepository
    {
        return AlertCheckQueueRepositoryMother::inMemory();
    }
}
