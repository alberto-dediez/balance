<?php

namespace App\Tests\Unit\Security\Infrastructure\Repository\RecordedEvent;

use App\Security\Domain\Model\RecordedEvent\RecordedEventRepository;
use App\Tests\Unit\Security\Domain\Model\RecordedEvent\RecordedEventRepositoryTest;
use App\Security\Infrastructure\Repository\RecordedEvent\InMemoryRecordedEventRepository;

class InMemoryRecordedEventRepositoryTest extends RecordedEventRepositoryTest
{
    public function createSUT(): RecordedEventRepository
    {
        return new InMemoryRecordedEventRepository();
    }
}