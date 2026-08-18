<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\LedgerEntry;

use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryLedgerEntryRepository;

class LedgerEntryRepositoryMother
{
    public static function inMemory(): InMemoryLedgerEntryRepository
    {
        return new InMemoryLedgerEntryRepository();
    }
}
