<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\Settlement\Unsettle;

use App\Balance\Application\Settlement\Unsettle\UnsettleLedgerEntriesRequest;
use App\Balance\Application\Settlement\Unsettle\UnsettleLedgerEntriesResponse;
use App\Balance\Application\Settlement\Unsettle\UnsettleLedgerEntriesService;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryUnsettled;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryLedgerEntryRepository;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\LedgerEntry\LedgerEntryMother;
use App\Tests\Unit\Balance\Domain\Model\LedgerEntry\LedgerEntryRepositoryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UnsettleLedgerEntriesServiceTest extends TestCase
{
    private AccountId $accountId;
    private InMemoryLedgerEntryRepository $ledger;
    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->accountId = AccountId::generate();
        $this->ledger = LedgerEntryRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testItReleasesEveryEntryTiedToThePayout(): void
    {
        $payoutId = PayoutId::generate();

        $this->ledger->add($this->settled($payoutId, 48.0));
        $this->ledger->add($this->settled($payoutId, 16.0));

        $response = $this->unsettle($payoutId);

        $this->assertSame(2, $response->releasedCount);
        $this->assertSame(0.0, $this->ledger->balanceFor(LedgerBeneficiary::Account, (string) $this->accountId)->settledAmount());
        $this->assertSame(64.0, $this->ledger->balanceFor(LedgerBeneficiary::Account, (string) $this->accountId)->pendingAmount());

        $events = $this->spy->events();
        $this->assertCount(2, $events);
        $this->assertInstanceOf(LedgerEntryUnsettled::class, $events[0]);
        $this->assertInstanceOf(LedgerEntryUnsettled::class, $events[1]);
    }

    public function testItReleasesNothingWhenNoEntryMatchesThePayout(): void
    {
        $response = $this->unsettle(PayoutId::generate());

        $this->assertSame(0, $response->releasedCount);
        $this->assertSame([], $this->spy->events());
    }

    public function testItIgnoresEntriesTiedToOtherPayouts(): void
    {
        $released = PayoutId::generate();
        $untouched = PayoutId::generate();

        $this->ledger->add($this->settled($released, 48.0));
        $this->ledger->add($this->settled($untouched, 20.0));

        $response = $this->unsettle($released);

        $this->assertSame(1, $response->releasedCount);
        $this->assertCount(1, $this->spy->events());
    }

    public function testItDoesNotReReleaseEntriesAlreadyUnsettled(): void
    {
        $payoutId = PayoutId::generate();

        $this->ledger->add($this->settled($payoutId, 48.0));

        $this->unsettle($payoutId);
        $this->spy->events();

        $second = $this->unsettle($payoutId);

        $this->assertSame(0, $second->releasedCount);
    }

    private function settled(PayoutId $payoutId, float $amount): LedgerEntry
    {
        $entry = LedgerEntryMother::forAccount($this->accountId, $amount);
        $entry->settle($payoutId);
        $this->ledger->add($entry);

        return $entry;
    }

    private function unsettle(PayoutId $payoutId): UnsettleLedgerEntriesResponse
    {
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();

        return (new UnsettleLedgerEntriesService($this->ledger))(new UnsettleLedgerEntriesRequest($payoutId));
    }
}
