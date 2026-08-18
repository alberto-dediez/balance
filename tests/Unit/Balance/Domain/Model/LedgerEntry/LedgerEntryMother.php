<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\LedgerEntry;

use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryType;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Security\Domain\Model\Account\AccountId;

class LedgerEntryMother
{
    public static function forAccount(?AccountId $accountId = null, float $amount = 48.0): LedgerEntry
    {
        return self::recorded(
            type: LedgerEntryType::ContributionNet,
            beneficiary: LedgerBeneficiary::Account,
            beneficiaryId: (string) ($accountId ?? AccountId::generate()),
            amount: $amount,
        );
    }

    public static function forProject(?ProjectId $projectId = null, float $amount = 80.0): LedgerEntry
    {
        return self::recorded(
            type: LedgerEntryType::ContributionNet,
            beneficiary: LedgerBeneficiary::Project,
            beneficiaryId: (string) ($projectId ?? ProjectId::generate()),
            amount: $amount,
        );
    }

    public static function forBioscore(float $amount = 20.0, LedgerEntryType $type = LedgerEntryType::ManagementFee): LedgerEntry
    {
        return self::recorded(
            type: $type,
            beneficiary: LedgerBeneficiary::Bioscore,
            beneficiaryId: null,
            amount: $amount,
        );
    }

    public static function unassigned(float $amount = 16.0): LedgerEntry
    {
        return self::recorded(
            type: LedgerEntryType::ContributionNet,
            beneficiary: LedgerBeneficiary::Unassigned,
            beneficiaryId: null,
            amount: $amount,
        );
    }

    public static function recorded(
        LedgerEntryType $type = LedgerEntryType::ContributionNet,
        LedgerBeneficiary $beneficiary = LedgerBeneficiary::Bioscore,
        ?string $beneficiaryId = null,
        float $amount = 10.0,
        ?ProjectContributionId $projectContributionId = null,
        ?string $concept = null,
        ?\DateTimeImmutable $occurredAt = null,
    ): LedgerEntry {
        $ledgerEntry = new LedgerEntry(id: LedgerEntryId::generate());

        $ledgerEntry->recordEntry(
            type: $type,
            beneficiary: $beneficiary,
            beneficiaryId: $beneficiaryId,
            amount: $amount,
            currency: 'EUR',
            projectContributionId: $projectContributionId,
            concept: $concept,
            occurredAt: $occurredAt,
        );

        return $ledgerEntry;
    }
}
