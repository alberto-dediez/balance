<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payment;

interface PaymentRepository
{
    public function add(Payment $payment): void;

    public function find(PaymentId $paymentId): ?Payment;

    public function findOneByExternalId(string $externalId): ?Payment;

    /** @return array<Payment> */
    public function findByFilter(PaymentFilter $filter): array;

    public function totalResultsByFilter(PaymentFilter $filter): int;
}
