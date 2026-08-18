<?php

declare(strict_types=1);

namespace App\Balance\Application\Settlement\Settle;

use App\Balance\Application\Settlement\Prepare\PrepareSettlementRequest;
use App\Balance\Application\Settlement\Prepare\PrepareSettlementService;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Payments\Application\Payout\Create\CreatePayoutRequest;
use App\Payments\Application\Payout\Create\CreatePayoutService;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Security\Domain\Model\User\User;

final class SettleLedgerEntriesService
{
    public function __construct(
        private LedgerEntryRepository $ledgerEntryRepository,
        private PayeeRepository $payeeRepository,
        private PrepareSettlementService $prepareSettlement,
        private CreatePayoutService $createPayout,
    ) {}

    public function __invoke(SettleLedgerEntriesRequest $request, User $user): SettleLedgerEntriesResponse
    {
        $settlement = $this->prepareSettlement->__invoke(
            new PrepareSettlementRequest($request->beneficiary, $request->beneficiaryId, $request->until),
            $user,
        );

        if ($settlement->isEmpty() || $settlement->amount <= 0.0) {
            throw new NothingToSettleException($request->beneficiaryId);
        }

        $payee = $this->payeeRepository->findOneBySubject(
            $this->payeeTypeFor($request->beneficiary),
            $request->beneficiaryId,
        );

        if (null === $payee) {
            throw new \InvalidArgumentException('Payee not found');
        }

        $payout = $this->createPayout->__invoke(
            new CreatePayoutRequest(
                payeeId: $payee->id(),
                baseAmount: $settlement->amount,
                linesCount: count($settlement->entries),
                periodFrom: $settlement->periodFrom,
                periodTo: $settlement->periodTo,
                notes: $request->notes,
            ),
            $user,
        );

        foreach ($settlement->entries as $entry) {
            $entry->settle($payout->payoutId);

            $this->ledgerEntryRepository->add($entry);
        }

        return new SettleLedgerEntriesResponse(
            payoutId: $payout->payoutId,
            number: $payout->number,
            amount: $settlement->amount,
            linesCount: count($settlement->entries),
        );
    }

    private function payeeTypeFor(LedgerBeneficiary $beneficiary): PayeeType
    {
        return match ($beneficiary) {
            LedgerBeneficiary::Account => PayeeType::Account,
            LedgerBeneficiary::Project => PayeeType::Project,
            default => throw new \InvalidArgumentException('Only accounts and projects can be settled.'),
        };
    }
}
