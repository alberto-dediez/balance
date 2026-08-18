<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Treasury\View;

use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;

final class OwnerBalanceView
{
    /** @var list<OwnerBalanceRow> */
    private array $rows = [];

    /**
     * @param array<LedgerEntry>    $entries
     * @param array<string, string> $projectNames
     * @param array<string, string> $purchaseReferences
     */
    public function __construct(array $entries, array $projectNames, array $purchaseReferences)
    {
        foreach ($entries as $entry) {
            $this->rows[] = new OwnerBalanceRow(
                entry: $entry,
                projectName: $projectNames[(string) $entry->projectId()] ?? null,
                purchaseReference: $purchaseReferences[(string) $entry->creditPurchaseId()] ?? null,
            );
        }
    }

    /** @return list<OwnerBalanceRow> */
    public function rows(): array
    {
        return $this->rows;
    }
}
