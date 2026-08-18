<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Persistence\InMemory\Repository;

use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payee\PayeeFilter;
use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Domain\Model\Payee\PayeeType;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryPayeeRepository implements PayeeRepository
{
    /** @param array<string, Payee> $payees */
    public function __construct(
        private array $payees = [],
    ) {}

    public function add(Payee $payee): void
    {
        $this->payees[(string) $payee->id()] = $payee;
    }

    public function find(PayeeId $payeeId): ?Payee
    {
        return $this->payees[(string) $payeeId] ?? null;
    }

    public function findOneBySubject(PayeeType $type, string $subjectId): ?Payee
    {
        foreach ($this->payees as $payee) {
            if ($payee->type() === $type && $payee->subjectId() === $subjectId) {
                return $payee;
            }
        }

        return null;
    }

    private function matches(Payee $payee, PayeeFilter $filter): bool
    {
        if (null !== $filter->type && $filter->type !== $payee->type()) {
            return false;
        }

        if (null !== $filter->subjectId && $filter->subjectId !== $payee->subjectId()) {
            return false;
        }

        if (null !== $filter->status && $filter->status !== $payee->status()) {
            return false;
        }

        if (empty($filter->search)) {
            return true;
        }

        $search = strtolower($filter->search);

        return str_contains(strtolower($payee->legalName()), $search)
            || str_contains(strtolower((string) $payee->taxId()), $search);
    }

    /** @return array<int, Payee> */
    public function findByFilter(PayeeFilter $filter): array
    {
        $results = [];

        $offset = $filter->page * $filter->limit;

        foreach ($this->payees as $payee) {
            if (!$this->matches($payee, $filter)) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $payee;
        }

        return $results;
    }

    public function totalResultsByFilter(PayeeFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
