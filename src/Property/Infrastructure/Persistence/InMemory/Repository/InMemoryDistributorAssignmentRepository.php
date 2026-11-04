<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Persistence\InMemory\Repository;

use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentFilter;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentId;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryDistributorAssignmentRepository implements DistributorAssignmentRepository
{
    /** @param array<string, DistributorAssignment> $assignments */
    public function __construct(private array $assignments = []) {}

    public function add(DistributorAssignment $assignment): void
    {
        $this->assignments[(string) $assignment->id()] = $assignment;
    }

    public function remove(DistributorAssignment $assignment): void
    {
        unset($this->assignments[(string) $assignment->id()]);
    }

    public function find(DistributorAssignmentId $id): ?DistributorAssignment
    {
        $assignment = $this->assignments[(string) $id] ?? null;

        return $assignment instanceof DistributorAssignment && !$assignment->isRemoved() ? $assignment : null;
    }

    public function findForAccount(AccountId $distributorAccountId, AccountId $accountId): ?DistributorAssignment
    {
        return $this->findByFilter(new DistributorAssignmentFilter(distributorAccountId: $distributorAccountId, accountId: $accountId, limit: 1))[0] ?? null;
    }

    public function findForProperty(AccountId $distributorAccountId, PropertyId $propertyId): ?DistributorAssignment
    {
        return $this->findByFilter(new DistributorAssignmentFilter(distributorAccountId: $distributorAccountId, propertyId: $propertyId, limit: 1))[0] ?? null;
    }

    /** @return list<DistributorAssignment> */
    public function findByFilter(DistributorAssignmentFilter $filter): array
    {
        $matching = array_values(array_filter(
            $this->assignments,
            fn(DistributorAssignment $assignment): bool => $this->matches($assignment, $filter),
        ));

        $ascending = 'registeredAt' === $filter->orderBy && 'ASC' === $filter->orderWay;

        usort(
            $matching,
            static fn(DistributorAssignment $a, DistributorAssignment $b): int => $ascending
                ? $a->registeredAt() <=> $b->registeredAt()
                : $b->registeredAt() <=> $a->registeredAt(),
        );

        if ($filter->limit > 0) {
            return array_slice($matching, $filter->page * $filter->limit, $filter->limit);
        }

        return $matching;
    }

    public function totalResultsByFilter(DistributorAssignmentFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }

    private function matches(DistributorAssignment $assignment, DistributorAssignmentFilter $filter): bool
    {
        if ($assignment->isRemoved()) {
            return false;
        }

        if ($filter->distributorAccountId instanceof AccountId && !$this->sameAccount($filter->distributorAccountId, $assignment->distributorAccountId())) {
            return false;
        }

        if ($filter->propertyId instanceof PropertyId && !$this->sameProperty($filter->propertyId, $assignment->propertyId())) {
            return false;
        }

        return $this->matchesTarget($assignment, $filter);
    }

    private function matchesTarget(DistributorAssignment $assignment, DistributorAssignmentFilter $filter): bool
    {
        $hasAccountCondition = $filter->accountId instanceof AccountId;
        $hasPropertiesCondition = null !== $filter->propertyIds && [] !== $filter->propertyIds;

        $matchesAccount = $filter->accountId instanceof AccountId && $this->sameAccount($filter->accountId, $assignment->accountId());
        $matchesProperties = $hasPropertiesCondition && $this->inProperties($assignment->propertyId(), $filter->propertyIds ?? []);

        if ($hasAccountCondition && $hasPropertiesCondition) {
            return $matchesAccount || $matchesProperties;
        }

        if ($hasAccountCondition) {
            return $matchesAccount;
        }

        if ($hasPropertiesCondition) {
            return $matchesProperties;
        }

        return [] !== $filter->propertyIds;
    }

    private function sameAccount(AccountId $expected, ?AccountId $actual): bool
    {
        return $actual instanceof AccountId && $expected->equals($actual);
    }

    private function sameProperty(PropertyId $expected, ?PropertyId $actual): bool
    {
        return $actual instanceof PropertyId && $expected->equals($actual);
    }

    /** @param list<PropertyId> $propertyIds */
    private function inProperties(?PropertyId $propertyId, array $propertyIds): bool
    {
        if (!$propertyId instanceof PropertyId) {
            return false;
        }

        foreach ($propertyIds as $candidate) {
            if ($candidate->equals($propertyId)) {
                return true;
            }
        }

        return false;
    }
}
