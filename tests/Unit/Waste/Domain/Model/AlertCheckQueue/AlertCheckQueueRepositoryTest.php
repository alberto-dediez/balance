<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\AlertCheckQueue;

use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueId;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueRepository;
use PHPUnit\Framework\TestCase;

abstract class AlertCheckQueueRepositoryTest extends TestCase
{
    public function testAddEntryToRepository(): void
    {
        $repository = $this->createSUT();
        $entry = AlertCheckQueueMother::pendingEntry();

        $repository->add($entry);

        $all = $repository->findAll();

        $this->assertCount(1, $all);
        $this->assertTrue($entry->id()->equals($all[0]->id()));
    }

    public function testFindReturnsEntryById(): void
    {
        $repository = $this->createSUT();
        $entry = AlertCheckQueueMother::pendingEntry();

        $this->assertNull($repository->find($entry->id()));

        $repository->add($entry);

        $found = $repository->find($entry->id());
        $this->assertNotNull($found);
        $this->assertTrue($entry->id()->equals($found->id()));
    }

    public function testFindReturnsNullWhenNotFound(): void
    {
        $repository = $this->createSUT();

        $this->assertNull($repository->find(AlertCheckQueueId::generate()));
    }

    public function testExistsForReturnsTrueWhenEntryExists(): void
    {
        $repository = $this->createSUT();

        $accountId = new AccountId('account-exists-test');
        $checkDate = new \DateTimeImmutable('2024-06-01');

        $repository->add(AlertCheckQueueMother::forAccountAndDate($accountId, $checkDate));

        $this->assertTrue($repository->existsFor($accountId, $checkDate));
    }

    public function testExistsForReturnsFalseWhenEntryDoesNotExist(): void
    {
        $repository = $this->createSUT();

        $this->assertFalse($repository->existsFor(new AccountId('unknown-account'), new \DateTimeImmutable('2024-06-01')));
    }

    public function testExistsForMatchesByAccountAndDate(): void
    {
        $repository = $this->createSUT();

        $accountId = new AccountId('account-id-test');
        $checkDate = new \DateTimeImmutable('2024-06-01');
        $otherDate = new \DateTimeImmutable('2024-06-02');

        $repository->add(AlertCheckQueueMother::forAccountAndDate($accountId, $checkDate));

        $this->assertTrue($repository->existsFor($accountId, $checkDate));
        $this->assertFalse($repository->existsFor($accountId, $otherDate));
        $this->assertFalse($repository->existsFor(new AccountId('other-account'), $checkDate));
    }

    public function testFindAllReturnsAllEntries(): void
    {
        $repository = $this->createSUT();

        $repository->add(AlertCheckQueueMother::random());
        $repository->add(AlertCheckQueueMother::random());
        $repository->add(AlertCheckQueueMother::random());

        $this->assertCount(3, $repository->findAll());
    }

    public function testFindAllReturnsEmptyWhenNoEntries(): void
    {
        $repository = $this->createSUT();

        $this->assertCount(0, $repository->findAll());
    }

    public function testRemoveDeletesEntry(): void
    {
        $repository = $this->createSUT();

        $entry = AlertCheckQueueMother::pendingEntry();
        $repository->add($entry);

        $this->assertCount(1, $repository->findAll());

        $repository->remove($entry);

        $this->assertCount(0, $repository->findAll());
    }

    public function testRemoveOnlyDeletesTargetEntry(): void
    {
        $repository = $this->createSUT();

        $entryA = AlertCheckQueueMother::random();
        $entryB = AlertCheckQueueMother::random();

        $repository->add($entryA);
        $repository->add($entryB);

        $repository->remove($entryA);

        $remaining = $repository->findAll();

        $this->assertCount(1, $remaining);
        $this->assertTrue($entryB->id()->equals($remaining[0]->id()));
    }

    abstract protected function createSUT(): AlertCheckQueueRepository;
}
