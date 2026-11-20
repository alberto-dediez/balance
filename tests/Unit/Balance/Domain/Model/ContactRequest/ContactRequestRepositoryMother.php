<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ContactRequest;

use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryContactRequestRepository;

class ContactRequestRepositoryMother
{
    public static function inMemory(): InMemoryContactRequestRepository
    {
        return new InMemoryContactRequestRepository();
    }
}
