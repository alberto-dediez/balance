<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Domain\Model\Event;

use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryEventRepository;

class EventRepositoryMother
{
    public static function inMemory(): InMemoryEventRepository
    {
        return new InMemoryEventRepository();
    }
}
