<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\LedgerEntry\Check;

use App\Balance\Application\LedgerEntry\Check\CheckLedgerRequest;
use App\Balance\Application\LedgerEntry\Check\CheckLedgerResponse;
use App\Balance\Application\LedgerEntry\Check\CheckLedgerService;
use App\Balance\Application\LedgerEntry\Check\LedgerFinding;
use App\Balance\Application\LedgerEntry\Check\LedgerFindingCode;
use App\Balance\Application\LedgerEntry\LedgerEntryAuthorizer;
use App\Balance\Application\LedgerEntry\Record\RecordContributionLedgerEntriesRequest;
use App\Balance\Application\LedgerEntry\Record\RecordContributionLedgerEntriesService;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\LedgerEntry\LedgerCheckCompleted;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryFilter;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\AllocatedCreditLine;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryCreditPurchaseRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryLedgerEntryRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\LedgerEntry\LedgerEntryMother;
use App\Tests\Unit\Balance\Domain\Model\LedgerEntry\LedgerEntryRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Payments\Domain\Model\Payout\PayoutRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class CheckLedgerServiceTest extends TestCase
{
    private AccountId $accountId;
    private ProjectId $projectId;
    private InMemoryProjectContributionRepository $contributions;
    private InMemoryCreditPurchaseRepository $purchases;
    private InMemoryProjectRepository $projects;
    private InMemoryLedgerEntryRepository $ledger;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->accountId = AccountId::generate();
        $this->projectId = ProjectId::generate();
        $this->contributions = ProjectContributionRepositoryMother::inMemory();
        $this->purchases = CreditPurchaseRepositoryMother::inMemory();
        $this->projects = ProjectRepositoryMother::inMemory();
        $this->ledger = LedgerEntryRepositoryMother::inMemory();

        $this->projects->add(ProjectMother::contributable(projectType: ProjectType::SocialProject, id: $this->projectId));
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testAFullyRecordedContributionRaisesNoFinding(): void
    {
        $contribution = $this->givenPaidContribution();
        $this->givenRecordedEntries($contribution);

        $response = $this->check();

        $this->assertFalse($response->hasFindings());
        $this->assertSame(1, $response->checkedContributions);
    }

    public function testAPaidContributionWithoutEntriesIsReported(): void
    {
        $this->givenPaidContribution();

        $response = $this->check();

        $this->assertCount(1, $response->findings);
        $this->assertSame(LedgerFindingCode::PaidContributionWithoutEntries, $response->findings[0]->code);
        $this->assertFalse($response->findings[0]->repaired);
    }

    public function testAnImportedContributionWithoutEntriesIsNotReported(): void
    {
        $this->contributions->add(ProjectContributionMother::imported($this->accountId, $this->projectId, 1.0));

        $response = $this->check(repair: true);

        $this->assertFalse($response->hasFindings());
        $this->assertSame(1, $response->checkedContributions);
    }

    public function testRepairRecordsTheMissingEntries(): void
    {
        $contribution = $this->givenPaidContribution();

        $response = $this->check(repair: true);

        $this->assertSame(1, $response->repairedCount());
        $this->assertSame($contribution->totalAmount(), $this->ledger->recordedAmountFor($contribution->id()));
        $this->assertFalse($this->check()->hasFindings());
    }

    public function testAMismatchingSumIsReported(): void
    {
        $contribution = $this->givenPaidContribution();
        $this->givenRecordedEntries($contribution);

        $this->ledger->add(LedgerEntryMother::recorded(
            amount: 5.0,
            projectContributionId: $contribution->id(),
        ));

        $response = $this->check();

        $this->assertCount(1, $response->findings);
        $this->assertSame(LedgerFindingCode::ContributionSumMismatch, $response->findings[0]->code);
    }

    public function testASerialSplitBetweenTwoLotsIsNotReportedAsOverallocated(): void
    {
        $source = CreditPurchaseMother::transferred($this->accountId, $this->projectId, 100.0, 70.0);
        $this->purchases->add($source);

        $destinationAccountId = AccountId::generate();

        $destination = CreditPurchaseMother::receivedLot(
            reference: (string) $source->reference(),
            purchasedTonnes: 100.0,
            offsetTonnes: 30.0,
            lotNumber: 2,
            accountId: $destinationAccountId,
            projectId: $this->projectId,
        );
        $this->purchases->add($destination);

        $this->givenAllocatedContribution($this->accountId, $source->id(), 30.0, 0.0);
        $this->givenAllocatedContribution($destinationAccountId, $destination->id(), 70.0, 30.0);

        $response = $this->check();

        $this->assertSame([], array_values(array_filter(
            $response->findings,
            static fn(LedgerFinding $finding): bool => LedgerFindingCode::OverallocatedCreditPurchase === $finding->code,
        )));
    }

    public function testEntriesOfAnUnpaidContributionAreReportedAsOrphans(): void
    {
        $contribution = ProjectContributionMother::pending($this->accountId, $this->projectId, 1.0);
        $this->contributions->add($contribution);

        $this->ledger->add(LedgerEntryMother::recorded(projectContributionId: $contribution->id()));

        $response = $this->check();

        $this->assertCount(1, $response->findings);
        $this->assertSame(LedgerFindingCode::OrphanEntries, $response->findings[0]->code);
    }

    public function testItOnlyChecksTheContributionsOfThePeriod(): void
    {
        $contribution = $this->givenPaidContribution();

        $this->assertCount(1, $this->check()->findings);

        $response = $this->check(from: new \DateTimeImmutable('+1 day'), to: new \DateTimeImmutable('+2 days'));

        $this->assertFalse($response->hasFindings());
        $this->assertSame(0, $response->checkedContributions);
        $this->assertNotNull($contribution->paidAt());
    }

    public function testItPublishesTheFindingsSoThatAdministratorsCanBeNotified(): void
    {
        $spy = new SpyDomainEventSubscriber();

        $this->givenPaidContribution();

        $this->check();

        $event = $spy->lastEvent();

        $this->assertInstanceOf(LedgerCheckCompleted::class, $event);
        $this->assertCount(1, $event->findings());
        $this->assertSame(LedgerFindingCode::PaidContributionWithoutEntries->value, $event->findings()[0]['code']);
    }

    public function testItPublishesNothingWhenTheLedgerIsConsistent(): void
    {
        $spy = new SpyDomainEventSubscriber();

        $contribution = $this->givenPaidContribution();
        $this->givenRecordedEntries($contribution);

        $this->check();

        $this->assertNotInstanceOf(LedgerCheckCompleted::class, $spy->lastEvent());
    }

    public function testItDeniesNonAdminUsers(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        (new CheckLedgerService(
            $this->contributions,
            $this->purchases,
            $this->ledger,
            PayoutRepositoryMother::inMemory(),
            new RecordContributionLedgerEntriesService(
                $this->contributions,
                $this->purchases,
                $this->projects,
                $this->ledger,
            ),
            new LedgerEntryAuthorizer(),
        ))(new CheckLedgerRequest(), UserMother::ownerUser());
    }

    private function givenPaidContribution(): ProjectContribution
    {
        $contribution = ProjectContributionMother::paid($this->accountId, $this->projectId, 1.0);

        $this->contributions->add($contribution);

        return $contribution;
    }

    private function givenAllocatedContribution(
        AccountId $accountId,
        CreditPurchaseId $creditPurchaseId,
        float $tonnes,
        float $startTonnes,
    ): void {
        $contribution = ProjectContributionMother::paid($accountId, $this->projectId, $tonnes);
        $contribution->allocateCredits([
            new AllocatedCreditLine(
                position: 0,
                creditPurchaseId: $creditPurchaseId,
                purchaseReference: 'SERIAL-1',
                tonnes: $tonnes,
                startTonnes: $startTonnes,
            ),
        ]);

        $this->contributions->add($contribution);

        $this->givenRecordedEntries($contribution);
    }

    private function givenRecordedEntries(ProjectContribution $contribution): void
    {
        (new RecordContributionLedgerEntriesService(
            $this->contributions,
            $this->purchases,
            $this->projects,
            $this->ledger,
        ))(new RecordContributionLedgerEntriesRequest($contribution->id()));

        $this->assertNotSame([], $this->ledger->findByFilter(
            new LedgerEntryFilter(projectContributionId: $contribution->id(), limit: 0),
        ));
    }

    private function check(bool $repair = false, ?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null): CheckLedgerResponse
    {
        return (new CheckLedgerService(
            $this->contributions,
            $this->purchases,
            $this->ledger,
            PayoutRepositoryMother::inMemory(),
            new RecordContributionLedgerEntriesService(
                $this->contributions,
                $this->purchases,
                $this->projects,
                $this->ledger,
            ),
            new LedgerEntryAuthorizer(),
        ))(new CheckLedgerRequest(repair: $repair, from: $from, to: $to), UserMother::adminUser());
    }
}
