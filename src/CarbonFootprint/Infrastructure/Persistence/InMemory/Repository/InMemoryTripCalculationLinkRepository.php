<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkFilter;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryTripCalculationLinkRepository implements TripCalculationLinkRepository
{
    /** @param array<string, TripCalculationLink> $tripCalculationLinks */
    public function __construct(private array $tripCalculationLinks = []) {}

    public function add(TripCalculationLink $tripCalculationLink): void
    {
        $this->tripCalculationLinks[(string) $tripCalculationLink->id()] = $tripCalculationLink;
    }

    public function find(TripCalculationLinkId $tripCalculationLinkId): ?TripCalculationLink
    {
        return $this->tripCalculationLinks[(string) $tripCalculationLinkId] ?? null;
    }

    public function findByPropertyId(PropertyId $propertyId): ?TripCalculationLink
    {
        foreach ($this->tripCalculationLinks as $tripCalculationLink) {
            $subjectId = $tripCalculationLink->propertyId();

            if ($subjectId instanceof PropertyId && $subjectId->equals($propertyId)) {
                return $tripCalculationLink;
            }
        }

        return null;
    }

    public function findForAccount(AccountId $accountId): ?TripCalculationLink
    {
        $found = null;

        foreach ($this->tripCalculationLinks as $tripCalculationLink) {
            $linkAccountId = $tripCalculationLink->accountId();

            if ($tripCalculationLink->isForProperty() || !$linkAccountId instanceof AccountId || !$accountId->equals($linkAccountId)) {
                continue;
            }

            if (null === $found || $tripCalculationLink->registeredAt() < $found->registeredAt()) {
                $found = $tripCalculationLink;
            }
        }

        return $found;
    }

    /** @return list<TripCalculationLink> */
    public function findByFilter(TripCalculationLinkFilter $filter): array
    {
        $results = [];

        foreach ($this->tripCalculationLinks as $tripCalculationLink) {
            if ($this->matches($tripCalculationLink, $filter)) {
                $results[] = $tripCalculationLink;
            }
        }

        if ($filter->limit > 0) {
            $results = array_slice($results, $filter->page * $filter->limit, $filter->limit);
        }

        return $results;
    }

    public function totalResultsByFilter(TripCalculationLinkFilter $filter): int
    {
        $count = 0;

        foreach ($this->tripCalculationLinks as $tripCalculationLink) {
            if ($this->matches($tripCalculationLink, $filter)) {
                ++$count;
            }
        }

        return $count;
    }

    public function findByToken(#[\SensitiveParameter] string $token): ?TripCalculationLink
    {
        foreach ($this->tripCalculationLinks as $tripCalculationLink) {
            if (hash_equals($tripCalculationLink->token(), $token)) {
                return $tripCalculationLink;
            }
        }

        return null;
    }

    private function matches(TripCalculationLink $tripCalculationLink, TripCalculationLinkFilter $filter): bool
    {
        if (null !== $filter->accountId) {
            $accountId = $tripCalculationLink->accountId();

            if (!$accountId instanceof AccountId || !$accountId->equals($filter->accountId)) {
                return false;
            }
        }

        if (null !== $filter->propertyId) {
            $propertyId = $tripCalculationLink->propertyId();

            if (!$propertyId instanceof PropertyId || !$propertyId->equals($filter->propertyId)) {
                return false;
            }
        }

        return true;
    }
}
