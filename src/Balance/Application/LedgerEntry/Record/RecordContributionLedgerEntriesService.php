<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Record;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryFilter;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryType;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionCreditAllocation;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Domain\Model\Account\AccountId;

final class RecordContributionLedgerEntriesService
{
    private const float MIN_ALLOCATABLE_TONNES = 0.000001;

    public function __construct(
        private ProjectContributionRepository $projectContributionRepository,
        private CreditPurchaseRepository $creditPurchaseRepository,
        private ProjectRepository $projectRepository,
        private LedgerEntryRepository $ledgerEntryRepository,
    ) {}

    public function __invoke(RecordContributionLedgerEntriesRequest $request): RecordContributionLedgerEntriesResponse
    {
        $projectContribution = $this->projectContributionRepository->find($request->projectContributionId);

        if (null === $projectContribution) {
            throw new \InvalidArgumentException('Project contribution not found');
        }

        if (!$projectContribution->isPaid() || $this->alreadyRecorded($projectContribution)) {
            return new RecordContributionLedgerEntriesResponse();
        }

        $plan = $this->plan($projectContribution);

        $occurredAt = $projectContribution->paidAt() ?? new \DateTimeImmutable('NOW');

        $entriesCount = 0;
        $recordedAmount = 0.0;
        $unassignedAmount = 0.0;

        foreach ($plan as $planned) {
            $ledgerEntry = new LedgerEntry(id: LedgerEntryId::generate());

            $ledgerEntry->recordEntry(
                type: $planned->type,
                beneficiary: $planned->beneficiary,
                beneficiaryId: $planned->beneficiaryId,
                amount: $planned->amount,
                currency: $projectContribution->currency(),
                projectContributionId: $projectContribution->id(),
                projectId: $projectContribution->projectId(),
                creditPurchaseId: $planned->creditPurchaseId,
                tonnes: $planned->tonnes,
                occurredAt: $occurredAt,
            );

            $this->ledgerEntryRepository->add($ledgerEntry);

            ++$entriesCount;
            $recordedAmount += $planned->amount;

            if (LedgerBeneficiary::Unassigned === $planned->beneficiary) {
                $unassignedAmount += $planned->amount;
            }
        }

        return new RecordContributionLedgerEntriesResponse(
            entriesCount: $entriesCount,
            recordedAmount: round($recordedAmount, 2),
            unassignedAmount: round($unassignedAmount, 2),
        );
    }

    private function alreadyRecorded(ProjectContribution $projectContribution): bool
    {
        return $this->ledgerEntryRepository->totalResultsByFilter(
            new LedgerEntryFilter(projectContributionId: $projectContribution->id()),
        ) > 0;
    }

    /** @return list<PlannedLedgerEntry> */
    private function plan(ProjectContribution $projectContribution): array
    {
        $netEntries = $this->planNet($projectContribution);

        $this->absorbRoundingResidue($netEntries, $projectContribution->contributionAmount());

        $entries = $netEntries;

        $entries[] = new PlannedLedgerEntry(
            type: LedgerEntryType::ManagementFee,
            beneficiary: LedgerBeneficiary::Bioscore,
            beneficiaryId: null,
            amount: round($projectContribution->managementFeeAmount(), 2),
        );

        $entries[] = new PlannedLedgerEntry(
            type: LedgerEntryType::Vat,
            beneficiary: LedgerBeneficiary::Bioscore,
            beneficiaryId: null,
            amount: round($projectContribution->vatAmount(), 2),
        );

        return array_values(array_filter(
            $entries,
            static fn(PlannedLedgerEntry $entry): bool => 0.0 !== $entry->amount || null !== $entry->tonnes,
        ));
    }

    /** @return list<PlannedLedgerEntry> */
    private function planNet(ProjectContribution $projectContribution): array
    {
        $netAmount = $projectContribution->contributionAmount();
        $projectId = $projectContribution->projectId();

        if (!$projectId instanceof ProjectId) {
            return [];
        }

        $project = $this->projectRepository->find($projectId);

        if (null === $project || ProjectType::EmissionCompensation !== $project->projectType()) {
            return [
                new PlannedLedgerEntry(
                    type: LedgerEntryType::ContributionNet,
                    beneficiary: LedgerBeneficiary::Project,
                    beneficiaryId: (string) $projectId,
                    amount: round($netAmount, 2),
                ),
            ];
        }

        return $this->planAllocatedNet($projectContribution, $netAmount);
    }

    /** @return list<PlannedLedgerEntry> */
    private function planAllocatedNet(ProjectContribution $projectContribution, float $netAmount): array
    {
        $tonnes = $projectContribution->tonnes();

        if ($tonnes <= 0.0) {
            return [];
        }

        $accountId = $projectContribution->accountId();
        $entries = [];

        foreach ($projectContribution->creditAllocations() as $allocation) {
            $beneficiary = $this->beneficiaryFor($allocation, $accountId);

            $entries[] = new PlannedLedgerEntry(
                type: LedgerEntryType::ContributionNet,
                beneficiary: $beneficiary,
                beneficiaryId: LedgerBeneficiary::Account === $beneficiary ? (string) $accountId : null,
                amount: round($netAmount * $allocation->tonnes() / $tonnes, 2),
                tonnes: $allocation->tonnes(),
                creditPurchaseId: $allocation->creditPurchaseId(),
            );
        }

        $unassignedTonnes = $tonnes - $projectContribution->allocatedTonnes();

        if ($unassignedTonnes > self::MIN_ALLOCATABLE_TONNES) {
            $entries[] = new PlannedLedgerEntry(
                type: LedgerEntryType::ContributionNet,
                beneficiary: LedgerBeneficiary::Unassigned,
                beneficiaryId: null,
                amount: round($netAmount * $unassignedTonnes / $tonnes, 2),
                tonnes: $unassignedTonnes,
            );
        }

        return $entries;
    }

    private function beneficiaryFor(ProjectContributionCreditAllocation $allocation, ?AccountId $accountId): LedgerBeneficiary
    {
        $creditPurchase = $this->creditPurchaseRepository->find($allocation->creditPurchaseId());

        if (!$accountId instanceof AccountId || null === $creditPurchase) {
            return LedgerBeneficiary::Bioscore;
        }

        return CreditPurchasePaymentType::PaidByClient === $creditPurchase->paymentType()
            ? LedgerBeneficiary::Account
            : LedgerBeneficiary::Bioscore;
    }

    /** @param list<PlannedLedgerEntry> $entries */
    private function absorbRoundingResidue(array $entries, float $expectedAmount): void
    {
        if ([] === $entries) {
            return;
        }

        $sum = 0.0;

        foreach ($entries as $entry) {
            $sum += $entry->amount;
        }

        $residue = round($expectedAmount - $sum, 2);

        if (0.0 === $residue) {
            return;
        }

        $last = $entries[count($entries) - 1];
        $last->amount = round($last->amount + $residue, 2);
    }
}
