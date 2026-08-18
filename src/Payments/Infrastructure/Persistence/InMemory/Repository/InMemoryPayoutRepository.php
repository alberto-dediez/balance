<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Persistence\InMemory\Repository;

use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Domain\Model\Payout\Payout;
use App\Payments\Domain\Model\Payout\PayoutFilter;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Payments\Domain\Model\Payout\PayoutRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryPayoutRepository implements PayoutRepository
{
    /** @param array<string, Payout> $payouts */
    public function __construct(
        private array $payouts = [],
    ) {}

    public function add(Payout $payout): void
    {
        $this->payouts[(string) $payout->id()] = $payout;
    }

    public function find(PayoutId $payoutId): ?Payout
    {
        return $this->payouts[(string) $payoutId] ?? null;
    }

    public function findOneByNumber(string $number): ?Payout
    {
        foreach ($this->payouts as $payout) {
            if ($payout->number() === $number) {
                return $payout;
            }
        }

        return null;
    }

    private function matches(Payout $payout, PayoutFilter $filter): bool
    {
        if (null !== $filter->payeeId && (string) $filter->payeeId !== (string) $payout->payeeId()) {
            return false;
        }

        if (null !== $filter->payeeIds) {
            $ids = array_map(static fn(PayeeId $payeeId): string => (string) $payeeId, $filter->payeeIds);

            if (!in_array((string) $payout->payeeId(), $ids, true)) {
                return false;
            }
        }

        if (null !== $filter->status && $filter->status !== $payout->status()) {
            return false;
        }

        if (!empty($filter->number) && !str_contains((string) $payout->number(), $filter->number)) {
            return false;
        }

        if (!empty($filter->reference) && !str_contains((string) $payout->reference(), $filter->reference)) {
            return false;
        }

        if ($filter->from instanceof \DateTimeImmutable && $payout->createdAt() < $filter->from) {
            return false;
        }

        return !($filter->to instanceof \DateTimeImmutable && $payout->createdAt() > $filter->to);
    }

    /** @return array<int, Payout> */
    public function findByFilter(PayoutFilter $filter): array
    {
        $results = [];

        $offset = $filter->page * $filter->limit;

        foreach ($this->payouts as $payout) {
            if (!$this->matches($payout, $filter)) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $payout;
        }

        return $results;
    }

    public function totalResultsByFilter(PayoutFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }

    /** @return list<string> */
    public function numbersOfYear(int $year): array
    {
        $numbers = [];

        foreach ($this->payouts as $payout) {
            $number = $payout->number();

            if (null !== $number && str_starts_with($number, sprintf('%d/', $year))) {
                $numbers[] = $number;
            }
        }

        sort($numbers);

        return $numbers;
    }
}
