<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form;

use App\Balance\Application\LedgerEntry\Adjust\RecordLedgerAdjustmentRequest;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;

class LedgerAdjustmentFormData
{
    public const string TARGET_SEPARATOR = '/';

    public function __construct(
        public ?string $target = null,
        public ?float $amount = null,
        public string $concept = '',
        public ?LedgerEntryId $reversesEntryId = null,
    ) {}

    public static function toReverse(LedgerEntry $ledgerEntry): self
    {
        return new self(
            target: self::targetOf($ledgerEntry->beneficiary(), $ledgerEntry->beneficiaryId()),
            amount: -$ledgerEntry->amount(),
            reversesEntryId: $ledgerEntry->id(),
        );
    }

    public function toRequest(): RecordLedgerAdjustmentRequest
    {
        return new RecordLedgerAdjustmentRequest(
            beneficiary: $this->beneficiary(),
            beneficiaryId: $this->beneficiaryId(),
            amount: (float) $this->amount,
            concept: $this->concept,
            reversesEntryId: $this->reversesEntryId,
        );
    }

    public function beneficiary(): LedgerBeneficiary
    {
        return LedgerBeneficiary::from(explode(self::TARGET_SEPARATOR, (string) $this->target, 2)[0]);
    }

    public function beneficiaryId(): ?string
    {
        return explode(self::TARGET_SEPARATOR, (string) $this->target, 2)[1] ?? null;
    }

    private static function targetOf(LedgerBeneficiary $beneficiary, ?string $beneficiaryId): string
    {
        return null !== $beneficiaryId
            ? $beneficiary->value . self::TARGET_SEPARATOR . $beneficiaryId
            : $beneficiary->value;
    }
}
