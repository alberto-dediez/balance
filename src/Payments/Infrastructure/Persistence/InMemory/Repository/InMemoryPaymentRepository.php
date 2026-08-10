<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Persistence\InMemory\Repository;

use App\Payments\Domain\Model\Payment\Payment;
use App\Payments\Domain\Model\Payment\PaymentFilter;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Payment\PaymentRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryPaymentRepository implements PaymentRepository
{
    /** @param array<string, Payment> $payments */
    public function __construct(
        private array $payments = [],
    ) {}

    public function add(Payment $payment): void
    {
        $this->payments[(string) $payment->id()] = $payment;
    }

    public function find(PaymentId $paymentId): ?Payment
    {
        return $this->payments[(string) $paymentId] ?? null;
    }

    public function findOneByExternalId(string $externalId): ?Payment
    {
        foreach ($this->payments as $payment) {
            if ($payment->externalId() === $externalId) {
                return $payment;
            }
        }

        return null;
    }

    private function matches(Payment $payment, PaymentFilter $filter): bool
    {
        if (null !== $filter->subjectType && $filter->subjectType !== $payment->subjectType()) {
            return false;
        }

        if (null !== $filter->subjectId && $filter->subjectId !== $payment->subjectId()) {
            return false;
        }

        if (null !== $filter->status && $filter->status !== $payment->status()) {
            return false;
        }

        return null === $filter->externalId || $filter->externalId === $payment->externalId();
    }

    /** @return array<int, Payment> */
    public function findByFilter(PaymentFilter $filter): array
    {
        $results = [];
        $offset = $filter->page * $filter->limit;

        foreach ($this->payments as $payment) {
            if (!$this->matches($payment, $filter)) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $payment;
        }

        return $results;
    }

    public function totalResultsByFilter(PaymentFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
