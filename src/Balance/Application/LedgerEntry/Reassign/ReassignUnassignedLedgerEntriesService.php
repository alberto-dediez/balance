<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Reassign;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryFilter;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryType;
use App\Balance\Domain\Model\ProjectContribution\AllocatedCreditLine;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Domain\Model\Account\AccountId;

final class ReassignUnassignedLedgerEntriesService
{
    private const float MIN_ALLOCATABLE_TONNES = 0.000001;

    public function __construct(
        private LedgerEntryRepository $ledgerEntryRepository,
        private ProjectContributionRepository $projectContributionRepository,
        private CreditPurchaseRepository $creditPurchaseRepository,
    ) {}

    public function __invoke(ReassignUnassignedLedgerEntriesRequest $request): ReassignUnassignedLedgerEntriesResponse
    {
        $contribution = $this->projectContributionRepository->find($request->projectContributionId);

        if (null === $contribution || [] === $request->lines) {
            return new ReassignUnassignedLedgerEntriesResponse();
        }

        $reassignedAmount = 0.0;
        $reassignedTonnes = 0.0;

        $available = array_map(static fn(AllocatedCreditLine $line): float => $line->tonnes, $request->lines);

        foreach ($this->unassignedEntries($contribution) as $entry) {
            $pool = $this->remainingTonnesOf($entry);

            foreach ($request->lines as $index => $line) {
                if ($pool <= self::MIN_ALLOCATABLE_TONNES) {
                    break;
                }

                if ($available[$index] <= self::MIN_ALLOCATABLE_TONNES) {
                    continue;
                }

                $covered = min($pool, $available[$index]);
                $amount = $this->amountFor($entry, $covered);

                $this->recordReversal($entry, $contribution, $covered, $amount);
                $this->recordReassignment($entry, $contribution, $line, $covered, $amount);

                $pool -= $covered;
                $available[$index] -= $covered;
                $reassignedTonnes += $covered;
                $reassignedAmount += $amount;
            }
        }

        return new ReassignUnassignedLedgerEntriesResponse(
            reassignedAmount: round($reassignedAmount, 2),
            reassignedTonnes: round($reassignedTonnes, 6),
        );
    }

    /** @return list<LedgerEntry> */
    private function unassignedEntries(ProjectContribution $contribution): array
    {
        return array_values(array_filter(
            $this->ledgerEntryRepository->findByFilter(new LedgerEntryFilter(
                type: LedgerEntryType::ContributionNet,
                beneficiary: LedgerBeneficiary::Unassigned,
                projectContributionId: $contribution->id(),
                limit: 0,
            )),
            static fn(LedgerEntry $entry): bool => null !== $entry->tonnes() && $entry->tonnes() > 0.0,
        ));
    }

    private function remainingTonnesOf(LedgerEntry $entry): float
    {
        $reversed = 0.0;

        foreach ($this->ledgerEntryRepository->findByFilter(new LedgerEntryFilter(
            type: LedgerEntryType::Adjustment,
            beneficiary: LedgerBeneficiary::Unassigned,
            projectContributionId: $entry->projectContributionId(),
            limit: 0,
        )) as $adjustment) {
            if ((string) $adjustment->reversesEntryId() !== (string) $entry->id()) {
                continue;
            }

            $reversed += abs((float) $adjustment->tonnes());
        }

        return round((float) $entry->tonnes() - $reversed, 6);
    }

    private function amountFor(LedgerEntry $entry, float $tonnes): float
    {
        $entryTonnes = (float) $entry->tonnes();

        if ($entryTonnes <= 0.0) {
            return 0.0;
        }

        return round($entry->amount() * $tonnes / $entryTonnes, 2);
    }

    private function recordReversal(LedgerEntry $entry, ProjectContribution $contribution, float $tonnes, float $amount): void
    {
        $reversal = new LedgerEntry(id: LedgerEntryId::generate());

        $reversal->recordEntry(
            type: LedgerEntryType::Adjustment,
            beneficiary: LedgerBeneficiary::Unassigned,
            beneficiaryId: null,
            amount: -$amount,
            currency: $entry->currency(),
            projectContributionId: $contribution->id(),
            projectId: $contribution->projectId(),
            tonnes: -$tonnes,
            reversesEntryId: $entry->id(),
            concept: 'Reversión de toneladas sin respaldo ahora cubiertas por un lote validado',
        );

        $this->ledgerEntryRepository->add($reversal);
    }

    private function recordReassignment(
        LedgerEntry $entry,
        ProjectContribution $contribution,
        AllocatedCreditLine $line,
        float $tonnes,
        float $amount,
    ): void {
        $accountId = $contribution->accountId();
        $creditPurchase = $this->creditPurchaseRepository->find($line->creditPurchaseId);

        $paidByClient = null !== $creditPurchase
            && CreditPurchasePaymentType::PaidByClient === $creditPurchase->paymentType()
            && $accountId instanceof AccountId;

        $reassigned = new LedgerEntry(id: LedgerEntryId::generate());

        $reassigned->recordEntry(
            type: LedgerEntryType::ContributionNet,
            beneficiary: $paidByClient ? LedgerBeneficiary::Account : LedgerBeneficiary::Bioscore,
            beneficiaryId: $paidByClient ? (string) $accountId : null,
            amount: $amount,
            currency: $entry->currency(),
            projectContributionId: $contribution->id(),
            projectId: $contribution->projectId(),
            creditPurchaseId: $line->creditPurchaseId,
            tonnes: $tonnes,
        );

        $this->ledgerEntryRepository->add($reassigned);
    }
}
