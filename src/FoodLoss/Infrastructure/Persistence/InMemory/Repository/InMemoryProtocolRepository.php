<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\Protocol\Protocol;
use App\FoodLoss\Domain\Model\Protocol\ProtocolFilter;
use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryProtocolRepository implements ProtocolRepository
{
    /** @param array<string, Protocol> $protocols */
    public function __construct(
        private array $protocols = [],
    ) {}

    public function add(Protocol $protocol): void
    {
        $this->protocols[(string) $protocol->id()] = $protocol;
    }

    public function remove(Protocol $protocol): void
    {
        if (!isset($this->protocols[(string) $protocol->id()])) {
            return;
        }

        unset($this->protocols[(string) $protocol->id()]);
    }

    public function find(ProtocolId $protocolId): ?Protocol
    {
        $protocol = $this->protocols[(string) $protocolId] ?? null;

        return ($protocol && false == $protocol->isRemoved())
            ? $protocol
            : null
        ;
    }

    /** @return array<int, Protocol> */
    public function findByFilter(ProtocolFilter $filter): array
    {
        $results = [];

        if (0 === count($this->protocols)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->protocols as $protocol) {
            if (true == $protocol->isRemoved()) {
                continue;
            }

            if (
                !empty($filter->name)
                && !str_contains(strtolower($protocol->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (
                $filter->accountId instanceof AccountId
                && (string) $protocol->accountId() !== (string) $filter->accountId
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

            $results[] = $protocol;
        }

        return $results;
    }

    public function totalResultsByFilter(ProtocolFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
