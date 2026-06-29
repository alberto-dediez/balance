<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Domain\Model\Event;

use App\EventOperations\Domain\Model\Event\EventRepository;

class InMemoryEventRepositoryTest extends EventRepositoryTest
{
    protected function createSUT(): EventRepository
    {
        return EventRepositoryMother::inMemory();
    }
}
