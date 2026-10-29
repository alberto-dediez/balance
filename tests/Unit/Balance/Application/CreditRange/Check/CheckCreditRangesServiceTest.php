<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\CreditRange\Check;

use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Application\CreditRange\Check\CheckCreditRangesRequest;
use App\Balance\Application\CreditRange\Check\CheckCreditRangesResponse;
use App\Balance\Application\CreditRange\Check\CheckCreditRangesService;
use App\Balance\Application\CreditRange\Check\CreditRangeFinding;
use App\Balance\Application\CreditRange\Check\CreditRangeFindingCode;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRangeCorrected;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseTransferred;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\AllocatedCreditLine;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryCreditPurchaseRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryRecordedEventRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Event\EventRegistry;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\RecordedEvent\RecordedEventMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class CheckCreditRangesServiceTest extends TestCase
{
    private InMemoryCreditPurchaseRepository $purchases;
    private InMemoryProjectContributionRepository $contributions;
    private InMemoryRecordedEventRepository $recordedEvents;
    private SpyDomainEventSubscriber $spySubscriber;
    private ProjectId $projectId;
    private \DateTimeImmutable $occurredOn;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        EventRegistry::instance()->registerEvent(CreditPurchaseTransferred::contextName(), CreditPurchaseTransferred::class);

        $this->purchases = CreditPurchaseRepositoryMother::inMemory();
        $this->contributions = ProjectContributionRepositoryMother::inMemory();
        $this->recordedEvents = new InMemoryRecordedEventRepository();
        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->projectId = ProjectId::generate();
        $this->occurredOn = new \DateTimeImmutable('2026-01-01 10:00:00');
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testASerialWithExactRangesHasNoFindings(): void
    {
        $source = $this->givenSerial();
        $this->givenReceivedLot($source, tonnes: 40.0, offset: 60.0, upperOffset: 0.0, lotNumber: 2);
        $this->givenReceivedLot($source, tonnes: 20.0, offset: 40.0, upperOffset: 40.0, lotNumber: 3);

        $response = $this->check();

        $this->assertSame([], $response->findings);
        $this->assertSame(2, $response->checkedLots);
    }

    public function testTheSecondTransferOfTheSameLotIsReportedAndRepaired(): void
    {
        $source = $this->givenSerial();
        $this->givenReceivedLot($source, tonnes: 40.0, offset: 60.0, upperOffset: 0.0, lotNumber: 2);
        $third = $this->givenReceivedLot($source, tonnes: 20.0, offset: 40.0, upperOffset: 0.0, lotNumber: 3);

        $this->assertSame(60.0, $third->availableTonnes());

        $response = $this->check(repair: true);

        $this->assertCount(1, $response->findings);
        $this->assertSame(CreditRangeFindingCode::RangeMismatch, $response->findings[0]->code);
        $this->assertTrue($response->findings[0]->repaired);
        $this->assertStringContainsString((string) $third->id(), $response->findings[0]->subjectId);

        $this->assertSame(40.0, $third->offsetTonnes());
        $this->assertSame(40.0, $third->upperOffsetTonnes());
        $this->assertSame(20.0, $third->availableTonnes());
        $this->assertSame(20.0, $third->requestedTonnes());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CreditPurchaseRangeCorrected::class, $lastEvent);
        $this->assertSame(0.0, $lastEvent->previousUpperOffsetTonnes());
        $this->assertSame(40.0, $lastEvent->upperOffsetTonnes());
    }

    public function testAChainFromAReceivedLotHasNoFindings(): void
    {
        $source = $this->givenSerial();
        $second = $this->givenReceivedLot($source, tonnes: 70.0, offset: 30.0, upperOffset: 0.0, lotNumber: 2);
        $this->givenReceivedLot($second, tonnes: 40.0, offset: 60.0, upperOffset: 0.0, lotNumber: 3);

        $response = $this->check();

        $this->assertSame([], $response->findings);
        $this->assertSame(2, $response->checkedLots);
    }

    public function testAnOverallocatedLotIsTrimmedToItsAllocationsAndReported(): void
    {
        $source = $this->givenSerial();
        $this->givenReceivedLot($source, tonnes: 40.0, offset: 60.0, upperOffset: 0.0, lotNumber: 2);
        $third = $this->givenReceivedLot($source, tonnes: 20.0, offset: 40.0, upperOffset: 0.0, lotNumber: 3);
        $this->givenAllocation($third, 50.0);

        $response = $this->check(repair: true);

        $this->assertSame(
            [
                [CreditRangeFindingCode::RangeMismatch, true],
                [CreditRangeFindingCode::OverallocatedAfterRepair, false],
            ],
            array_map(
                static fn(CreditRangeFinding $finding): array => [$finding->code, $finding->repaired],
                $response->findings,
            ),
        );
        $this->assertStringContainsString('30.000000', $response->findings[1]->detail);

        $this->assertSame(40.0, $third->offsetTonnes());
        $this->assertSame(10.0, $third->upperOffsetTonnes());
        $this->assertSame(50.0, $third->availableTonnes());

        $accountId = $third->accountId();

        $this->assertInstanceOf(AccountId::class, $accountId);

        $balance = (new CreditAvailability($this->purchases, $this->contributions))->for($accountId, $this->projectId);

        $this->assertEqualsWithDelta(0.0, $balance->availableTonnes(), 1e-9);
    }

    public function testARepairedOverallocatedLotIsOnlyReportedAsOverallocated(): void
    {
        $source = $this->givenSerial();
        $this->givenReceivedLot($source, tonnes: 40.0, offset: 60.0, upperOffset: 0.0, lotNumber: 2);
        $third = $this->givenReceivedLot($source, tonnes: 20.0, offset: 40.0, upperOffset: 0.0, lotNumber: 3);
        $this->givenAllocation($third, 50.0);

        $this->check(repair: true);
        $eventsBefore = count($this->spySubscriber->events());

        $response = $this->check(repair: true);

        $this->assertSame(
            [[CreditRangeFindingCode::OverallocatedAfterRepair, false]],
            array_map(
                static fn(CreditRangeFinding $finding): array => [$finding->code, $finding->repaired],
                $response->findings,
            ),
        );
        $this->assertSame(10.0, $third->upperOffsetTonnes());
        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }

    public function testTransfersOfTheSameSplitSharingTheSecondAreOrderedByLotNumber(): void
    {
        $source = $this->givenSerial();
        $lots = [
            $this->givenReceivedLot($source, tonnes: 30.0, offset: 70.0, upperOffset: 0.0, lotNumber: 2, recorded: false),
            $this->givenReceivedLot($source, tonnes: 30.0, offset: 40.0, upperOffset: 30.0, lotNumber: 3, recorded: false),
            $this->givenReceivedLot($source, tonnes: 30.0, offset: 10.0, upperOffset: 60.0, lotNumber: 4, recorded: false),
        ];
        $occurredOn = new \DateTimeImmutable('2026-01-01 12:00:00');

        foreach (array_reverse($lots) as $lot) {
            $this->givenRecordedTransfer($source->id(), $lot->id(), 30.0, $occurredOn);
        }

        $response = $this->check();

        $this->assertSame([], $response->findings);
        $this->assertSame(3, $response->checkedLots);
    }

    public function testAReceivedLotWithoutTransferEventIsReported(): void
    {
        $lot = CreditPurchaseMother::receivedLot(reference: 'SERIAL-1', projectId: $this->projectId);
        $this->purchases->add($lot);

        $response = $this->check(repair: true);

        $this->assertCount(1, $response->findings);
        $this->assertSame(CreditRangeFindingCode::MissingTransferEvent, $response->findings[0]->code);
        $this->assertFalse($response->findings[0]->repaired);
        $this->assertStringContainsString((string) $lot->id(), $response->findings[0]->subjectId);
    }

    public function testWithoutRepairNothingIsModified(): void
    {
        $source = $this->givenSerial();
        $this->givenReceivedLot($source, tonnes: 40.0, offset: 60.0, upperOffset: 0.0, lotNumber: 2);
        $third = $this->givenReceivedLot($source, tonnes: 20.0, offset: 40.0, upperOffset: 0.0, lotNumber: 3);
        $this->givenAllocation($third, 50.0);
        $eventsBefore = count($this->spySubscriber->events());

        $response = $this->check();

        $this->assertSame(
            [CreditRangeFindingCode::RangeMismatch, CreditRangeFindingCode::OverallocatedAfterRepair],
            array_map(static fn(CreditRangeFinding $finding): CreditRangeFindingCode => $finding->code, $response->findings),
        );
        $this->assertFalse($response->findings[0]->repaired);
        $this->assertSame(0.0, $third->upperOffsetTonnes());
        $this->assertSame(60.0, $third->availableTonnes());
        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }

    public function testALotThatCededOverItsExactRangeIsReportedWithoutChanges(): void
    {
        [$second, $third, $fourth] = $this->givenOvercededChain();
        $eventsBefore = count($this->spySubscriber->events());

        $response = $this->check();

        $this->assertSame(
            [
                [CreditRangeFindingCode::RangeMismatch, (string) $third->id(), false],
                [CreditRangeFindingCode::OverallocatedAfterRepair, (string) $third->id(), false],
                [CreditRangeFindingCode::RangeMismatch, (string) $fourth->id(), false],
                [CreditRangeFindingCode::OvercededRange, (string) $third->id(), false],
            ],
            $this->summary($response, $second, $third, $fourth),
        );
        $this->assertStringContainsString('30.000000 t ceded below the exact range', $response->findings[3]->detail);
        $this->assertStringContainsString((string) $fourth->id(), $response->findings[3]->detail);
        $this->assertStringContainsString('30.000000 t ceded and 0.000000 t allocated', $response->findings[1]->detail);

        $this->assertSame(10.0, $fourth->offsetTonnes());
        $this->assertSame(40.0, $fourth->upperOffsetTonnes());
        $this->assertSame(0.0, $third->upperOffsetTonnes());
        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }

    public function testRepairTrimsTheDestinationOfAnOvercededLotToTheFloorOfItsSource(): void
    {
        [$second, $third, $fourth] = $this->givenOvercededChain();

        $response = $this->check(repair: true);

        $this->assertSame(
            [
                [CreditRangeFindingCode::RangeMismatch, (string) $third->id(), true],
                [CreditRangeFindingCode::OverallocatedAfterRepair, (string) $third->id(), false],
                [CreditRangeFindingCode::RangeMismatch, (string) $fourth->id(), true],
                [CreditRangeFindingCode::OvercededRange, (string) $third->id(), true],
            ],
            $this->summary($response, $second, $third, $fourth),
        );

        $this->assertSame(40.0, $fourth->offsetTonnes());
        $this->assertSame(40.0, $fourth->upperOffsetTonnes());
        $this->assertSame(20.0, $fourth->availableTonnes());

        $this->assertSame(40.0, $third->offsetTonnes());
        $this->assertSame(10.0, $third->upperOffsetTonnes());
        $this->assertGreaterThanOrEqual(
            0.0,
            ($third->purchasedTonnes() ?? 0.0) - $third->offsetTonnes() - $third->upperOffsetTonnes() - $third->voidedTonnes(),
        );

        $this->assertTrue($this->rangeWasCorrected($fourth->id()));
        $this->assertBalanceCanBeComputed($third);
        $this->assertBalanceCanBeComputed($fourth);
    }

    public function testRepairTrimsAnAllocatedDestinationOfAnOvercededLotToItsAllocations(): void
    {
        [$second, $third, $fourth] = $this->givenOvercededChain();
        $this->givenAllocation($fourth, 30.0);

        $response = $this->check(repair: true);

        $this->assertSame(
            [
                [CreditRangeFindingCode::RangeMismatch, (string) $third->id(), true],
                [CreditRangeFindingCode::OverallocatedAfterRepair, (string) $third->id(), false],
                [CreditRangeFindingCode::RangeMismatch, (string) $fourth->id(), true],
                [CreditRangeFindingCode::OverallocatedAfterRepair, (string) $fourth->id(), false],
                [CreditRangeFindingCode::OvercededRange, (string) $third->id(), true],
            ],
            $this->summary($response, $second, $third, $fourth),
        );
        $this->assertStringContainsString('0.000000 t ceded and 10.000000 t allocated', $response->findings[3]->detail);

        $this->assertSame(40.0, $fourth->offsetTonnes());
        $this->assertSame(30.0, $fourth->upperOffsetTonnes());
        $this->assertSame(30.0, $fourth->availableTonnes());
        $this->assertBalanceCanBeComputed($fourth);
    }

    public function testAnOvercededRangeIsReportedAsRepairedOnceItsDestinationIsBounded(): void
    {
        [$second, $third, $fourth] = $this->givenOvercededChain();

        $this->check(repair: true);
        $eventsBefore = count($this->spySubscriber->events());

        $response = $this->check();

        $this->assertSame(
            [
                [CreditRangeFindingCode::OverallocatedAfterRepair, (string) $third->id(), false],
                [CreditRangeFindingCode::OvercededRange, (string) $third->id(), true],
            ],
            $this->summary($response, $second, $third, $fourth),
        );
        $this->assertStringContainsString((string) $fourth->id(), $response->findings[1]->detail);
        $this->assertSame(40.0, $fourth->offsetTonnes());
        $this->assertSame(40.0, $fourth->upperOffsetTonnes());
        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }

    public function testAnOwnerCannotCheckTheRanges(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->check(user: UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    /** @return array{CreditPurchase, CreditPurchase, CreditPurchase} */
    private function givenOvercededChain(): array
    {
        $source = $this->givenSerial();
        $second = $this->givenReceivedLot($source, tonnes: 40.0, offset: 60.0, upperOffset: 0.0, lotNumber: 2);
        $third = $this->givenReceivedLot($source, tonnes: 20.0, offset: 40.0, upperOffset: 0.0, lotNumber: 3);
        $fourth = $this->givenReceivedLot($third, tonnes: 50.0, offset: 10.0, upperOffset: 40.0, lotNumber: 4);

        return [$second, $third, $fourth];
    }

    /** @return list<array{CreditRangeFindingCode, string, bool}> */
    private function summary(CheckCreditRangesResponse $response, CreditPurchase ...$lots): array
    {
        return array_map(
            static function (CreditRangeFinding $finding) use ($lots): array {
                $lotIds = array_values(array_filter(
                    array_map(static fn(CreditPurchase $lot): string => (string) $lot->id(), $lots),
                    static fn(string $lotId): bool => str_contains($finding->subjectId, $lotId),
                ));

                return [$finding->code, $lotIds[0] ?? $finding->subjectId, $finding->repaired];
            },
            $response->findings,
        );
    }

    private function rangeWasCorrected(CreditPurchaseId $lotId): bool
    {
        foreach ($this->spySubscriber->events() as $event) {
            if ($event instanceof CreditPurchaseRangeCorrected && $event->creditPurchaseId()->equals($lotId)) {
                return true;
            }
        }

        return false;
    }

    private function assertBalanceCanBeComputed(CreditPurchase $lot): void
    {
        $accountId = $lot->accountId();

        $this->assertInstanceOf(AccountId::class, $accountId);

        $balance = (new CreditAvailability($this->purchases, $this->contributions))->for($accountId, $this->projectId);

        $this->assertGreaterThanOrEqual(0.0, $balance->purchasedTonnes());
    }

    private function givenSerial(): CreditPurchase
    {
        $source = CreditPurchaseMother::validated(
            AccountId::generate(),
            $this->projectId,
            100.0,
            CreditPurchasePaymentType::PaidByBioscore,
        );

        $this->purchases->add($source);

        return $source;
    }

    private function givenReceivedLot(
        CreditPurchase $source,
        float $tonnes,
        float $offset,
        float $upperOffset,
        int $lotNumber,
        bool $recorded = true,
    ): CreditPurchase {
        $lot = CreditPurchaseMother::receivedLot(
            reference: (string) $source->reference(),
            purchasedTonnes: 100.0,
            offsetTonnes: $offset,
            upperOffsetTonnes: $upperOffset,
            lotNumber: $lotNumber,
            projectId: $this->projectId,
        );

        $source->transfer(
            tonnes: $tonnes,
            amount: 0.0,
            destinationCreditPurchaseId: $lot->id(),
            destinationAccountId: AccountId::generate(),
            transferredBy: UserId::generate(),
        );

        $this->purchases->add($lot);
        $this->purchases->add($source);

        if ($recorded) {
            $this->givenRecordedTransfer($source->id(), $lot->id(), $tonnes);
        }

        return $lot;
    }

    private function givenRecordedTransfer(
        CreditPurchaseId $sourceId,
        CreditPurchaseId $destinationId,
        float $tonnes,
        ?\DateTimeImmutable $occurredOn = null,
    ): void {
        $this->occurredOn = $occurredOn ?? $this->occurredOn->modify('+1 minute');

        $this->recordedEvents->add(RecordedEventMother::from(
            UserId::generate(),
            new CreditPurchaseTransferred(
                creditPurchaseId: $sourceId,
                destinationCreditPurchaseId: $destinationId,
                destinationAccountId: AccountId::generate(),
                transferredTonnes: $tonnes,
                transferredAmount: 0.0,
                transferredBy: UserId::generate(),
                occurredOn: $this->occurredOn,
            ),
        ));
    }

    private function givenAllocation(CreditPurchase $lot, float $tonnes): void
    {
        $accountId = $lot->accountId();

        $this->assertInstanceOf(AccountId::class, $accountId);

        $contribution = ProjectContributionMother::paid($accountId, $this->projectId, $tonnes);
        $contribution->allocateCredits([
            new AllocatedCreditLine(
                position: 0,
                creditPurchaseId: $lot->id(),
                purchaseReference: $lot->reference(),
                tonnes: $tonnes,
                startTonnes: $lot->offsetTonnes(),
            ),
        ]);

        $this->contributions->add($contribution);
    }

    private function check(bool $repair = false, ?User $user = null): CheckCreditRangesResponse
    {
        return (new CheckCreditRangesService(
            $this->purchases,
            $this->recordedEvents,
            $this->contributions,
            new CreditPurchaseAuthorizer(),
        ))(new CheckCreditRangesRequest(repair: $repair), $user ?? UserMother::adminUser());
    }
}
