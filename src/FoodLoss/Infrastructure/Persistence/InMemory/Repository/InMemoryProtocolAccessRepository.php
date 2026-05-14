<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccess;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessFilter;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessId;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryProtocolAccessRepository implements ProtocolAccessRepository
{
    /** @param array<string, ProtocolAccess> $accesses */
    public function __construct(
        private array $accesses = [],
    ) {}

    public function add(ProtocolAccess $protocolAccess): void
    {
        $this->accesses[(string) $protocolAccess->id()] = $protocolAccess;
    }

    public function find(ProtocolAccessId $protocolAccessId): ?ProtocolAccess
    {
        return $this->accesses[(string) $protocolAccessId] ?? null;
    }

    /** @return array<int, ProtocolAccess> */
    public function findByFilter(ProtocolAccessFilter $filter): array
    {
        $results = [];

        if (0 === count($this->accesses)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->accesses as $protocolAccess) {
            if (
                null !== $filter->protocolId
                && (string) $protocolAccess->protocolId() !== (string) $filter->protocolId
            ) {
                continue;
            }

            if (
                null !== $filter->employeeId
                && (string) $protocolAccess->employeeId() !== (string) $filter->employeeId
            ) {
                continue;
            }

            if (
                null !== $filter->accountId
                && (string) $protocolAccess->accountId() !== (string) $filter->accountId
            ) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $protocolAccess;
        }

        return $results;
    }

    public function totalResultsByFilter(ProtocolAccessFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }

    /** @return array<ProtocolAccess> */
    public function findLastAccessPerEmployee(ProtocolId $protocolId): array
    {
        /** @var array<string, ProtocolAccess> $lastByEmployee */
        $lastByEmployee = [];

        foreach ($this->accesses as $protocolAccess) {
            if ((string) $protocolAccess->protocolId() !== (string) $protocolId) {
                continue;
            }

            $employeeKey = (string) $protocolAccess->employeeId();

            if (
                !isset($lastByEmployee[$employeeKey])
                || $protocolAccess->accessedAt() > $lastByEmployee[$employeeKey]->accessedAt()
            ) {
                $lastByEmployee[$employeeKey] = $protocolAccess;
            }
        }

        return array_values($lastByEmployee);
    }

    /** @return array<ProtocolAccess> */
    public function findHistoryByProtocolAndEmployee(ProtocolId $protocolId, EmployeeId $employeeId): array
    {
        $results = [];

        foreach ($this->accesses as $protocolAccess) {
            if ((string) $protocolAccess->protocolId() !== (string) $protocolId) {
                continue;
            }

            if ((string) $protocolAccess->employeeId() !== (string) $employeeId) {
                continue;
            }

            $results[] = $protocolAccess;
        }

        usort($results, static fn(ProtocolAccess $a, ProtocolAccess $b) => $b->accessedAt() <=> $a->accessedAt());

        return $results;
    }
}
