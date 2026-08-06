<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\Property\Domain\Model\Property\PropertyId;
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

    public function findByToken(#[\SensitiveParameter] string $token): ?TripCalculationLink
    {
        foreach ($this->tripCalculationLinks as $tripCalculationLink) {
            if (hash_equals($tripCalculationLink->token(), $token)) {
                return $tripCalculationLink;
            }
        }

        return null;
    }
}
