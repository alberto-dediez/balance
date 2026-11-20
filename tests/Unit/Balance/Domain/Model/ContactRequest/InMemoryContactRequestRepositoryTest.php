<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ContactRequest;

use App\Balance\Domain\Model\ContactRequest\ContactRequestRepository;

class InMemoryContactRequestRepositoryTest extends ContactRequestRepositoryTest
{
    protected function createSUT(): ContactRequestRepository
    {
        return ContactRequestRepositoryMother::inMemory();
    }
}
