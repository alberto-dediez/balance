<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRepository;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryStatus;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrineContestEntryRepository;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrineContestRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineContestEntryRepositoryTest extends ContestEntryRepositoryTest
{
    public function testRefreshDiscardsStaleState(): void
    {
        $repository = $this->createSUT();
        $entry = ContestEntryMother::pending($this->contest());
        $repository->add($entry);

        $this->manager()->getConnection()->executeStatement(
            'UPDATE contest_entries SET status = :status, entry_number = :entryNumber WHERE id = :id',
            ['status' => ContestEntryStatus::Confirmed->value, 'entryNumber' => 7, 'id' => (string) $entry->id()],
        );

        $this->assertTrue($repository->find($entry->id())?->isPendingPayment());

        $repository->refresh($entry);

        $this->assertFalse($entry->isPendingPayment());
        $this->assertSame(7, $entry->entryNumber());
    }

    protected function createSUT(): ContestEntryRepository
    {
        return new DoctrineContestEntryRepository($this->manager());
    }

    protected function contestRepository(): ContestRepository
    {
        return new DoctrineContestRepository($this->manager());
    }

    private function manager(): EntityManagerInterface
    {
        return $this->getContainer()->get(EntityManagerInterface::class);
    }
}
