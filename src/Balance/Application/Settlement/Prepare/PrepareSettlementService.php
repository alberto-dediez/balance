<?php

declare(strict_types=1);

namespace App\Balance\Application\Settlement\Prepare;

use App\Balance\Application\Settlement\SettlementAuthorizer;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class PrepareSettlementService
{
    public function __construct(
        private LedgerEntryRepository $ledgerEntryRepository,
        private SettlementAuthorizer $authorizer,
    ) {}

    public function __invoke(PrepareSettlementRequest $request, User $user): PrepareSettlementResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new LedgerEntry(id: LedgerEntryId::generate()), SecurityAction::Register, $user);

        $entries = $this->ledgerEntryRepository->findSettleable(
            $request->beneficiary,
            $request->beneficiaryId,
            $request->until,
        );

        if ([] === $entries) {
            return new PrepareSettlementResponse();
        }

        $amount = 0.0;
        $from = null;
        $to = null;

        foreach ($entries as $entry) {
            $amount += $entry->amount();

            $from = null === $from || $entry->occurredAt() < $from ? $entry->occurredAt() : $from;
            $to = null === $to || $entry->occurredAt() > $to ? $entry->occurredAt() : $to;
        }

        return new PrepareSettlementResponse(
            entries: $entries,
            amount: round($amount, 2),
            periodFrom: $from,
            periodTo: $to,
        );
    }
}
