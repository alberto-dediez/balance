<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Treasury\View;

use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;

final readonly class OwnerBalanceRow
{
    public function __construct(
        public LedgerEntry $entry,
        public ?string $projectName,
        public ?string $purchaseReference,
    ) {}
}
