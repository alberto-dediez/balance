<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Adjust;

use App\Balance\Application\LedgerEntry\LedgerEntryAuthorizer;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class RecordLedgerAdjustmentService
{
    public function __construct(
        private LedgerEntryRepository $ledgerEntryRepository,
        private LedgerEntryAuthorizer $authorizer,
    ) {}

    public function __invoke(RecordLedgerAdjustmentRequest $request, User $user): RecordLedgerAdjustmentResponse
    {
        $ledgerEntry = new LedgerEntry(id: LedgerEntryId::generate());

        $this->authorizer->denyUnlessCanDoThat($ledgerEntry, SecurityAction::Register, $user);

        $reversedEntry = null !== $request->reversesEntryId
            ? $this->ledgerEntryRepository->find($request->reversesEntryId)
            : null;

        if (null !== $request->reversesEntryId && null === $reversedEntry) {
            throw new \InvalidArgumentException('Ledger entry to reverse not found');
        }

        if (null !== $reversedEntry && !$reversedEntry->belongsTo($request->beneficiary, $request->beneficiaryId)) {
            throw new \InvalidArgumentException('A reversal must target the same beneficiary as the reversed entry');
        }

        $ledgerEntry->recordEntry(
            type: LedgerEntryType::Adjustment,
            beneficiary: $request->beneficiary,
            beneficiaryId: $request->beneficiaryId,
            amount: round($request->amount, 2),
            currency: $request->currency,
            projectContributionId: $request->projectContributionId ?? $reversedEntry?->projectContributionId(),
            projectId: $request->projectId ?? $reversedEntry?->projectId(),
            creditPurchaseId: $reversedEntry?->creditPurchaseId(),
            reversesEntryId: $request->reversesEntryId,
            concept: $request->concept,
            recordedBy: $user->id(),
        );

        $this->ledgerEntryRepository->add($ledgerEntry);

        return new RecordLedgerAdjustmentResponse($ledgerEntry->id());
    }
}
