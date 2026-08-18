<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\InMemory\Repository;

use App\Balance\Domain\Model\LedgerEntry\LedgerBalance;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryFilter;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Payments\Domain\Model\Payout\PayoutId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryLedgerEntryRepository implements LedgerEntryRepository
{
    /** @param array<string, LedgerEntry> $ledgerEntries */
    public function __construct(
        private array $ledgerEntries = [],
    ) {}

    public function add(LedgerEntry $ledgerEntry): void
    {
        $this->ledgerEntries[(string) $ledgerEntry->id()] = $ledgerEntry;
    }

    public function find(LedgerEntryId $ledgerEntryId): ?LedgerEntry
    {
        return $this->ledgerEntries[(string) $ledgerEntryId] ?? null;
    }

    private function matches(LedgerEntry $ledgerEntry, LedgerEntryFilter $filter): bool
    {
        if (null !== $filter->type && $filter->type !== $ledgerEntry->type()) {
            return false;
        }

        if (null !== $filter->types && [] !== $filter->types && !in_array($ledgerEntry->type(), $filter->types, true)) {
            return false;
        }

        if (null !== $filter->beneficiary && $filter->beneficiary !== $ledgerEntry->beneficiary()) {
            return false;
        }

        if (null !== $filter->beneficiaryId && $filter->beneficiaryId !== $ledgerEntry->beneficiaryId()) {
            return false;
        }

        if (
            null !== $filter->projectContributionId
            && (string) $filter->projectContributionId !== (string) $ledgerEntry->projectContributionId()
        ) {
            return false;
        }

        if (null !== $filter->projectId && (string) $filter->projectId !== (string) $ledgerEntry->projectId()) {
            return false;
        }

        if (
            null !== $filter->creditPurchaseId
            && (string) $filter->creditPurchaseId !== (string) $ledgerEntry->creditPurchaseId()
        ) {
            return false;
        }

        if (null !== $filter->payoutId && (string) $filter->payoutId !== (string) $ledgerEntry->payoutId()) {
            return false;
        }

        if (null !== $filter->settled && $filter->settled !== $ledgerEntry->isSettled()) {
            return false;
        }

        if ($filter->from instanceof \DateTimeImmutable && $ledgerEntry->occurredAt() < $filter->from) {
            return false;
        }

        return !($filter->to instanceof \DateTimeImmutable && $ledgerEntry->occurredAt() > $filter->to);
    }

    /** @return array<int, LedgerEntry> */
    public function findByFilter(LedgerEntryFilter $filter): array
    {
        $results = [];

        $offset = $filter->page * $filter->limit;

        foreach ($this->ledgerEntries as $ledgerEntry) {
            if (!$this->matches($ledgerEntry, $filter)) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $ledgerEntry;
        }

        return $results;
    }

    public function totalResultsByFilter(LedgerEntryFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }

    public function balanceFor(LedgerBeneficiary $beneficiary, ?string $beneficiaryId): LedgerBalance
    {
        $accrued = 0.0;
        $settled = 0.0;
        $entriesCount = 0;

        foreach ($this->ledgerEntries as $ledgerEntry) {
            if (!$ledgerEntry->belongsTo($beneficiary, $beneficiaryId)) {
                continue;
            }

            $accrued += $ledgerEntry->amount();
            ++$entriesCount;

            if ($ledgerEntry->isSettled()) {
                $settled += $ledgerEntry->amount();
            }
        }

        return new LedgerBalance(
            beneficiary: $beneficiary,
            beneficiaryId: $beneficiaryId,
            accruedAmount: round($accrued, 2),
            settledAmount: round($settled, 2),
            entriesCount: $entriesCount,
        );
    }

    /** @return array<string, LedgerBalance> */
    public function balancesByBeneficiary(LedgerBeneficiary $beneficiary): array
    {
        $beneficiaryIds = [];

        foreach ($this->ledgerEntries as $ledgerEntry) {
            if ($ledgerEntry->beneficiary() !== $beneficiary) {
                continue;
            }

            $beneficiaryIds[(string) $ledgerEntry->beneficiaryId()] = $ledgerEntry->beneficiaryId();
        }

        $balances = [];

        foreach ($beneficiaryIds as $key => $beneficiaryId) {
            $balances[$key] = $this->balanceFor($beneficiary, $beneficiaryId);
        }

        return $balances;
    }

    /** @return list<LedgerEntry> */
    public function findSettleable(LedgerBeneficiary $beneficiary, string $beneficiaryId, ?\DateTimeImmutable $until): array
    {
        if (!$beneficiary->isSettleable()) {
            return [];
        }

        $entries = $this->findByFilter(new LedgerEntryFilter(
            beneficiary: $beneficiary,
            beneficiaryId: $beneficiaryId,
            settled: false,
            to: $until,
            limit: 0,
        ));

        return array_values(array_filter(
            $entries,
            static fn(LedgerEntry $entry): bool => $entry->isSettleable(),
        ));
    }

    /** @return list<LedgerEntry> */
    public function findByPayout(PayoutId $payoutId): array
    {
        return array_values($this->findByFilter(new LedgerEntryFilter(payoutId: $payoutId, limit: 0)));
    }

    public function recordedAmountFor(ProjectContributionId $projectContributionId): float
    {
        $amount = 0.0;

        foreach ($this->findByFilter(new LedgerEntryFilter(projectContributionId: $projectContributionId, limit: 0)) as $ledgerEntry) {
            $amount += $ledgerEntry->amount();
        }

        return round($amount, 2);
    }

    /** @return array<string, float> */
    public function totalsByType(LedgerEntryFilter $filter): array
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        $totals = [];

        foreach ($this->findByFilter($clonedFilter) as $ledgerEntry) {
            $key = $ledgerEntry->type()->value;

            $totals[$key] = round(($totals[$key] ?? 0.0) + $ledgerEntry->amount(), 2);
        }

        return $totals;
    }
}
