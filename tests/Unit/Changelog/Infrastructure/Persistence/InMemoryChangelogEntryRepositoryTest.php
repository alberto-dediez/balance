<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Infrastructure\Persistence;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryRepository;
use App\Changelog\Infrastructure\Persistence\InMemory\InMemoryChangelogEntryRepository;

class InMemoryChangelogEntryRepositoryTest extends ChangelogEntryRepositoryTest
{
    protected function createRepository(): ChangelogEntryRepository
    {
        return new InMemoryChangelogEntryRepository();
    }

    protected function givenEntries(array $entries): ChangelogEntryRepository
    {
        $repository = new InMemoryChangelogEntryRepository();

        foreach ($entries as $entry) {
            $repository->add($entry);
        }

        return $repository;
    }
}
