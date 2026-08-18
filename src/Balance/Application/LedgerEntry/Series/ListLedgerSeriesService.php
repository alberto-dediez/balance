<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Series;

use App\Balance\Application\LedgerEntry\LedgerEntryAuthorizer;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryFilter;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class ListLedgerSeriesService
{
    public function __construct(
        private LedgerEntryRepository $repository,
        private LedgerEntryAuthorizer $authorizer,
    ) {}

    public function __invoke(ListLedgerSeriesRequest $request, User $user): ListLedgerSeriesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new LedgerEntry(id: LedgerEntryId::generate()), SecurityAction::Admin, $user);

        $months = $this->months($request->from, $request->to);
        $entries = $this->repository->findByFilter(new LedgerEntryFilter(from: $request->from, to: $request->to, limit: 0));

        $amountsByBeneficiary = [];

        foreach (LedgerBeneficiary::cases() as $beneficiary) {
            $amountsByBeneficiary[$beneficiary->value] = array_fill(0, count($months), 0.0);
        }

        $accrued = array_fill(0, count($months), 0.0);
        $settled = array_fill(0, count($months), 0.0);

        $index = array_flip($months);

        foreach ($entries as $entry) {
            $month = $entry->occurredAt()->format('Y-m');

            if (!isset($index[$month])) {
                continue;
            }

            $position = $index[$month];

            $amountsByBeneficiary[$entry->beneficiary()->value][$position] += $entry->amount();
            $accrued[$position] += $entry->amount();

            if ($entry->isSettled()) {
                $settled[$position] += $entry->amount();
            }
        }

        return new ListLedgerSeriesResponse(
            months: $months,
            amountsByBeneficiary: array_map(
                static fn(array $values): array => array_values(array_map(static fn(float $value): float => round($value, 2), $values)),
                $amountsByBeneficiary,
            ),
            accruedByMonth: array_values(array_map(static fn(float $value): float => round($value, 2), $accrued)),
            settledByMonth: array_values(array_map(static fn(float $value): float => round($value, 2), $settled)),
            topPending: $this->topPending($request->topBeneficiaries),
        );
    }

    /** @return list<string> */
    private function months(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $months = [];

        $cursor = $from->modify('first day of this month')->setTime(0, 0);
        $last = $to->modify('first day of this month')->setTime(0, 0);

        while ($cursor <= $last) {
            $months[] = $cursor->format('Y-m');
            $cursor = $cursor->modify('+1 month');
        }

        return $months;
    }

    /** @return list<array{id: string, amount: float}> */
    private function topPending(int $limit): array
    {
        $pending = [];

        foreach ([LedgerBeneficiary::Account, LedgerBeneficiary::Project] as $beneficiary) {
            foreach ($this->repository->balancesByBeneficiary($beneficiary) as $beneficiaryId => $balance) {
                if ($balance->pendingAmount() <= 0.0) {
                    continue;
                }

                $pending[] = ['id' => (string) $beneficiaryId, 'amount' => $balance->pendingAmount()];
            }
        }

        usort($pending, static fn(array $a, array $b): int => $b['amount'] <=> $a['amount']);

        return array_slice($pending, 0, $limit);
    }
}
