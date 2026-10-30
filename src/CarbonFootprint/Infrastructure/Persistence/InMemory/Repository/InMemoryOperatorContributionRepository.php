<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContribution;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionFilter;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionId;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryOperatorContributionRepository implements OperatorContributionRepository
{
    /** @param array<string, OperatorContribution> $contributions */
    public function __construct(
        private array $contributions = [],
    ) {}

    public function add(OperatorContribution $contribution): void
    {
        $this->contributions[(string) $contribution->id()] = $contribution;
    }

    public function find(OperatorContributionId $id): ?OperatorContribution
    {
        $contribution = $this->contributions[(string) $id] ?? null;

        return null !== $contribution && !$contribution->isRemoved() ? $contribution : null;
    }

    public function findByToken(#[\SensitiveParameter] string $token): ?OperatorContribution
    {
        if ('' === $token) {
            return null;
        }

        foreach ($this->contributions as $contribution) {
            if (!$contribution->isRemoved() && hash_equals($contribution->token(), $token)) {
                return $contribution;
            }
        }

        return null;
    }

    /** @return list<OperatorContribution> */
    public function findActiveByCompanyCarbonFootprint(CompanyCarbonFootprintId $id): array
    {
        return $this->findByFilter(new OperatorContributionFilter(
            companyCarbonFootprintId: $id,
            limit: 0,
        ));
    }

    public function findActiveByCompanyCarbonFootprintAndProperty(CompanyCarbonFootprintId $id, PropertyId $propertyId): ?OperatorContribution
    {
        $contributions = $this->findByFilter(new OperatorContributionFilter(
            companyCarbonFootprintId: $id,
            propertyId: $propertyId,
            limit: 1,
        ));

        return $contributions[0] ?? null;
    }

    public function findLatestByAccountAndProperty(AccountId $accountId, PropertyId $propertyId): ?OperatorContribution
    {
        $latest = null;

        foreach ($this->contributions as $contribution) {
            if (null === $contribution->accountId() || !$accountId->equals($contribution->accountId())) {
                continue;
            }

            if (null === $contribution->propertyId() || !$propertyId->equals($contribution->propertyId())) {
                continue;
            }

            if (null === $latest || $contribution->registeredAt() > $latest->registeredAt()) {
                $latest = $contribution;
            }
        }

        return $latest;
    }

    /** @return list<OperatorContribution> */
    public function findByFilter(OperatorContributionFilter $filter): array
    {
        $contributions = array_values($this->contributions);
        usort(
            $contributions,
            static fn(OperatorContribution $a, OperatorContribution $b): int => $a->registeredAt() <=> $b->registeredAt(),
        );

        $results = [];
        $offset = $filter->page * $filter->limit;

        foreach ($contributions as $contribution) {
            if ($contribution->isRemoved()) {
                continue;
            }

            if (null !== $filter->companyCarbonFootprintId && (null === $contribution->companyCarbonFootprintId() || !$filter->companyCarbonFootprintId->equals($contribution->companyCarbonFootprintId()))) {
                continue;
            }

            if (null !== $filter->accountId && (null === $contribution->accountId() || !$filter->accountId->equals($contribution->accountId()))) {
                continue;
            }

            if (null !== $filter->propertyId && (null === $contribution->propertyId() || !$filter->propertyId->equals($contribution->propertyId()))) {
                continue;
            }

            if (null !== $filter->status && $contribution->status() !== $filter->status) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if ($filter->limit > 0 && count($results) >= $filter->limit) {
                continue;
            }

            $results[] = $contribution;
        }

        return $results;
    }

    public function totalResultsByFilter(OperatorContributionFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
