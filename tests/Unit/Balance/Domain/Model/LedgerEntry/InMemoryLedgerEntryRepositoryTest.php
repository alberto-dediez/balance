<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\LedgerEntry;

use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;

class InMemoryLedgerEntryRepositoryTest extends LedgerEntryRepositoryTest
{
    protected function createSUT(): LedgerEntryRepository
    {
        return LedgerEntryRepositoryMother::inMemory();
    }
}
