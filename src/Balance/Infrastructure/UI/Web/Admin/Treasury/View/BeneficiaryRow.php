<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\Treasury\View;

use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;

final readonly class BeneficiaryRow
{
    public function __construct(
        public LedgerBeneficiary $beneficiary,
        public string $beneficiaryId,
        public string $name,
        public float $accrued,
        public float $settled,
        public float $pending,
        public ?string $payeeStatus,
        public string $detailPath,
        public string $settlePath,
    ) {}

    public function isSettleable(): bool
    {
        return 'ready' === $this->payeeStatus && $this->pending > 0.0;
    }
}
