<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Payout;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Payments\Domain\Model\Payout\PayoutStatementLine;
use App\Payments\Domain\Model\Payout\PayoutStatementLinesProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LedgerPayoutStatementLinesProvider implements PayoutStatementLinesProvider
{
    public function __construct(
        private LedgerEntryRepository $ledgerEntryRepository,
        private ProjectRepository $projectRepository,
        private CreditPurchaseRepository $creditPurchaseRepository,
        private TranslatorInterface $translator,
    ) {}

    /** @return list<PayoutStatementLine> */
    public function linesOf(PayoutId $payoutId): array
    {
        return array_map(
            fn(LedgerEntry $entry): PayoutStatementLine => new PayoutStatementLine(
                occurredAt: $entry->occurredAt(),
                concept: $entry->concept() ?? $this->translator->trans($entry->type()->value, [], 'ledgerEntryType'),
                projectName: $this->projectName($entry->projectId()),
                purchaseReference: $this->purchaseReference($entry->creditPurchaseId()),
                tonnes: $entry->tonnes(),
                amount: $entry->amount(),
            ),
            $this->ledgerEntryRepository->findByPayout($payoutId),
        );
    }

    private function projectName(?ProjectId $projectId): ?string
    {
        if (!$projectId instanceof ProjectId) {
            return null;
        }

        return $this->projectRepository->find($projectId)?->name();
    }

    private function purchaseReference(?CreditPurchaseId $creditPurchaseId): ?string
    {
        if (!$creditPurchaseId instanceof CreditPurchaseId) {
            return null;
        }

        return $this->creditPurchaseRepository->find($creditPurchaseId)?->reference();
    }
}
