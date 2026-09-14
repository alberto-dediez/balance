<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\ChangelogEntry;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Changelog\Infrastructure\Persistence\InMemory\InMemoryChangelogEntryRepository;

class ChangelogEntryRepositoryMother
{
    public static function withEntries(ChangelogEntry ...$entries): InMemoryChangelogEntryRepository
    {
        $repository = new InMemoryChangelogEntryRepository();

        foreach ($entries as $entry) {
            $repository->add($entry);
        }

        return $repository;
    }
}
