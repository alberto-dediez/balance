<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\LedgerEntry;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Payments\Domain\Model\Payout\PayoutId;

interface LedgerEntryRepository
{
    public function add(LedgerEntry $ledgerEntry): void;

    public function find(LedgerEntryId $ledgerEntryId): ?LedgerEntry;

    /** @return array<LedgerEntry> */
    public function findByFilter(LedgerEntryFilter $filter): array;

    public function totalResultsByFilter(LedgerEntryFilter $filter): int;

    public function balanceFor(LedgerBeneficiary $beneficiary, ?string $beneficiaryId): LedgerBalance;

    /** @return array<string, LedgerBalance> */
    public function balancesByBeneficiary(LedgerBeneficiary $beneficiary): array;

    /** @return list<LedgerEntry> */
    public function findSettleable(LedgerBeneficiary $beneficiary, string $beneficiaryId, ?\DateTimeImmutable $until): array;

    /** @return list<LedgerEntry> */
    public function findByPayout(PayoutId $payoutId): array;

    public function recordedAmountFor(ProjectContributionId $projectContributionId): float;

    /** @return array<string, float> */
    public function totalsByType(LedgerEntryFilter $filter): array;
}
