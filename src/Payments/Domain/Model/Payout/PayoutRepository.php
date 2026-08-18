<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

interface PayoutRepository
{
    public function add(Payout $payout): void;

    public function find(PayoutId $payoutId): ?Payout;

    public function findOneByNumber(string $number): ?Payout;

    /** @return array<Payout> */
    public function findByFilter(PayoutFilter $filter): array;

    public function totalResultsByFilter(PayoutFilter $filter): int;

    /** @return list<string> */
    public function numbersOfYear(int $year): array;
}
