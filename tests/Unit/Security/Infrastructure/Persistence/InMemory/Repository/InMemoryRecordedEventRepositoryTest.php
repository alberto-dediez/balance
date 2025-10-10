<?php

namespace App\Tests\Unit\Security\Infrastructure\Persistence\InMemory\Repository;

use App\Security\Domain\Model\RecordedEvent\RecordedEventRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryRecordedEventRepository;
use App\Tests\Unit\Security\Domain\Model\RecordedEvent\RecordedEventRepositoryTest;

class InMemoryRecordedEventRepositoryTest extends RecordedEventRepositoryTest
{
    public function createSUT(): RecordedEventRepository
    {
        return new InMemoryRecordedEventRepository();
    }
}
