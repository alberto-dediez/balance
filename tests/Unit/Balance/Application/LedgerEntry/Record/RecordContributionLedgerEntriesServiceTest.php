<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\LedgerEntry\Record;

use App\Balance\Application\LedgerEntry\Record\RecordContributionLedgerEntriesRequest;
use App\Balance\Application\LedgerEntry\Record\RecordContributionLedgerEntriesResponse;
use App\Balance\Application\LedgerEntry\Record\RecordContributionLedgerEntriesService;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryFilter;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryType;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\AllocatedCreditLine;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryCreditPurchaseRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryLedgerEntryRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\LedgerEntry\LedgerEntryRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use PHPUnit\Framework\TestCase;

class RecordContributionLedgerEntriesServiceTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testASocialProjectAccruesTheWholeNetToTheProject(): void
    {
        $this->givenProject(ProjectType::SocialProject);

        $contribution = $this->givenPaidContribution(1.0);

        $this->record($contribution);

        $entries = $this->entriesOf($contribution);

        $this->assertCount(3, $entries);
        $this->assertEntry($entries[0], LedgerEntryType::ContributionNet, LedgerBeneficiary::Project, (string) $this->projectId, 15.0);
        $this->assertEntry($entries[1], LedgerEntryType::ManagementFee, LedgerBeneficiary::Bioscore, null, 5.0);
        $this->assertEntry($entries[2], LedgerEntryType::Vat, LedgerBeneficiary::Bioscore, null, 4.2);
        $this->assertSame($contribution->totalAmount(), $this->ledger->recordedAmountFor($contribution->id()));
    }

    public function testALotPaidByTheClientAccruesTheNetToTheAccount(): void
    {
        $this->givenProject();

        $contribution = $this->givenPaidContribution(1.0);
        $this->givenAllocations($contribution, [[1.0, CreditPurchasePaymentType::PaidByClient]]);

        $this->record($contribution);

        $entries = $this->entriesOf($contribution);

        $this->assertEntry($entries[0], LedgerEntryType::ContributionNet, LedgerBeneficiary::Account, (string) $this->accountId, 15.0);
        $this->assertSame(1.0, $entries[0]->tonnes());
        $this->assertSame($contribution->totalAmount(), $this->ledger->recordedAmountFor($contribution->id()));
    }

    public function testALotPaidByBioscoreAccruesTheNetToBioscore(): void
    {
        $this->givenProject();

        $contribution = $this->givenPaidContribution(1.0);
        $this->givenAllocations($contribution, [[1.0, CreditPurchasePaymentType::PaidByBioscore]]);

        $this->record($contribution);

        $entries = $this->entriesOf($contribution);

        $this->assertEntry($entries[0], LedgerEntryType::ContributionNet, LedgerBeneficiary::Bioscore, null, 15.0);
        $this->assertSame(0.0, $this->ledger->balanceFor(LedgerBeneficiary::Account, (string) $this->accountId)->accruedAmount());
    }

    public function testTheNetIsSplitBetweenLotsOfBothPaymentTypes(): void
    {
        $this->givenProject();

        $contribution = $this->givenPaidContribution(1.0);
        $this->givenAllocations($contribution, [
            [0.6, CreditPurchasePaymentType::PaidByClient],
            [0.4, CreditPurchasePaymentType::PaidByBioscore],
        ]);

        $this->record($contribution);

        $entries = $this->entriesOf($contribution);

        $this->assertEntry($entries[0], LedgerEntryType::ContributionNet, LedgerBeneficiary::Account, (string) $this->accountId, 9.0);
        $this->assertEntry($entries[1], LedgerEntryType::ContributionNet, LedgerBeneficiary::Bioscore, null, 6.0);
        $this->assertSame($contribution->totalAmount(), $this->ledger->recordedAmountFor($contribution->id()));
    }

    public function testTonnesWithoutCreditBackingAccrueAsUnassigned(): void
    {
        $this->givenProject();

        $contribution = $this->givenPaidContribution(1.0);
        $this->givenAllocations($contribution, [[0.6, CreditPurchasePaymentType::PaidByClient]]);

        $this->record($contribution);

        $entries = $this->entriesOf($contribution);

        $this->assertEntry($entries[0], LedgerEntryType::ContributionNet, LedgerBeneficiary::Account, (string) $this->accountId, 9.0);
        $this->assertEntry($entries[1], LedgerEntryType::ContributionNet, LedgerBeneficiary::Unassigned, null, 6.0);
        $this->assertSame(0.4, round((float) $entries[1]->tonnes(), 6));
        $this->assertSame($contribution->totalAmount(), $this->ledger->recordedAmountFor($contribution->id()));
    }

    public function testAContributionWithoutAnyCreditAccruesFullyAsUnassigned(): void
    {
        $this->givenProject();

        $contribution = $this->givenPaidContribution(1.0);

        $this->record($contribution);

        $entries = $this->entriesOf($contribution);

        $this->assertEntry($entries[0], LedgerEntryType::ContributionNet, LedgerBeneficiary::Unassigned, null, 15.0);
        $this->assertSame(15.0, $this->ledger->balanceFor(LedgerBeneficiary::Unassigned, null)->accruedAmount());
    }

    public function testTheLastNetEntryAbsorbsTheRoundingResidue(): void
    {
        $this->givenProject();

        $contribution = $this->givenPaidContribution(0.07);
        $this->givenAllocations($contribution, [
            [0.035, CreditPurchasePaymentType::PaidByClient],
            [0.035, CreditPurchasePaymentType::PaidByClient],
        ]);

        $this->record($contribution);

        $entries = $this->entriesOf($contribution);

        $this->assertSame(0.53, $entries[0]->amount());
        $this->assertSame(0.52, $entries[1]->amount());
        $this->assertSame($contribution->totalAmount(), $this->ledger->recordedAmountFor($contribution->id()));
    }

    public function testAnAllocationTooSmallToBeWorthACentIsStillRecorded(): void
    {
        $this->givenProject();

        $contribution = $this->givenPaidContribution(1.0);
        $this->givenAllocations($contribution, [
            [0.0001, CreditPurchasePaymentType::PaidByClient],
            [0.9999, CreditPurchasePaymentType::PaidByBioscore],
        ]);

        $this->record($contribution);

        $entries = $this->entriesOf($contribution);

        $this->assertCount(4, $entries);
        $this->assertEntry($entries[0], LedgerEntryType::ContributionNet, LedgerBeneficiary::Account, (string) $this->accountId, 0.0);
        $this->assertSame(0.0001, $entries[0]->tonnes());
        $this->assertSame($contribution->totalAmount(), $this->ledger->recordedAmountFor($contribution->id()));
    }

    public function testRecordingIsIdempotent(): void
    {
        $this->givenProject(ProjectType::SocialProject);

        $contribution = $this->givenPaidContribution(1.0);

        $this->record($contribution);
        $response = $this->record($contribution);

        $this->assertSame(0, $response->entriesCount);
        $this->assertCount(3, $this->entriesOf($contribution));
    }

    public function testAnUnpaidContributionRecordsNothing(): void
    {
        $this->givenProject(ProjectType::SocialProject);

        $contribution = ProjectContributionMother::pending($this->accountId, $this->projectId, 1.0);
        $this->contributions->add($contribution);

        $response = $this->record($contribution);

        $this->assertSame(0, $response->entriesCount);
        $this->assertSame([], $this->entriesOf($contribution));
    }

    private function givenProject(ProjectType $projectType = ProjectType::EmissionCompensation): void
    {
        $this->projects->add(ProjectMother::contributable(projectType: $projectType, id: $this->projectId));
    }

    private function givenPaidContribution(float $tonnes): ProjectContribution
    {
        $contribution = ProjectContributionMother::paid($this->accountId, $this->projectId, $tonnes);

        $this->contributions->add($contribution);

        return $contribution;
    }

    /** @param list<array{0: float, 1: CreditPurchasePaymentType}> $lots */
    private function givenAllocations(ProjectContribution $contribution, array $lots): void
    {
        $lines = [];
        $position = 0;

        foreach ($lots as [$tonnes, $paymentType]) {
            $purchase = $this->givenValidatedPurchase($tonnes, $paymentType);

            $lines[] = new AllocatedCreditLine(
                position: $position,
                creditPurchaseId: $purchase->id(),
                purchaseReference: $purchase->reference(),
                tonnes: $tonnes,
                startTonnes: 0.0,
            );

            ++$position;
        }

        $contribution->allocateCredits($lines);

        $this->contributions->add($contribution);
    }

    private function givenValidatedPurchase(float $tonnes, CreditPurchasePaymentType $paymentType): CreditPurchase
    {
        $purchase = CreditPurchaseMother::validatedAt(
            new \DateTimeImmutable('-1 day'),
            $this->accountId,
            $this->projectId,
            $tonnes,
            $paymentType,
        );

        $this->purchases->add($purchase);

        return $purchase;
    }

    private function record(ProjectContribution $contribution): RecordContributionLedgerEntriesResponse
    {
        return (new RecordContributionLedgerEntriesService(
            $this->contributions,
            $this->purchases,
            $this->projects,
            $this->ledger,
        ))(new RecordContributionLedgerEntriesRequest($contribution->id()));
    }

    /** @return list<LedgerEntry> */
    private function entriesOf(ProjectContribution $contribution): array
    {
        return array_values($this->ledger->findByFilter(
            new LedgerEntryFilter(projectContributionId: $contribution->id(), limit: 0),
        ));
    }

    private function assertEntry(
        LedgerEntry $entry,
        LedgerEntryType $type,
        LedgerBeneficiary $beneficiary,
        ?string $beneficiaryId,
        float $amount,
    ): void {
        $this->assertSame($type, $entry->type());
        $this->assertSame($beneficiary, $entry->beneficiary());
        $this->assertSame($beneficiaryId, $entry->beneficiaryId());
        $this->assertSame($amount, $entry->amount());
    }
}
