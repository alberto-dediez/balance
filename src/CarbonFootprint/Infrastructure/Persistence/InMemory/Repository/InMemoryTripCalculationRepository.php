<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationFilter;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationId;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryTripCalculationRepository implements TripCalculationRepository
{
    /** @param array<string, TripCalculation> $tripCalculations */
    public function __construct(private array $tripCalculations = []) {}

    public function add(TripCalculation $tripCalculation): void
    {
        $this->tripCalculations[(string) $tripCalculation->id()] = $tripCalculation;
    }

    public function find(TripCalculationId $tripCalculationId): ?TripCalculation
    {
        return $this->tripCalculations[(string) $tripCalculationId] ?? null;
    }

    /** @return list<TripCalculation> */
    public function findByFilter(TripCalculationFilter $filter): array
    {
        $results = [];

        foreach ($this->tripCalculations as $tripCalculation) {
            if (!$this->matches($tripCalculation, $filter)) {
                continue;
            }

            $results[] = $tripCalculation;
        }

        $way = 'ASC' === $filter->orderWay ? 1 : -1;

        usort(
            $results,
            static fn(TripCalculation $a, TripCalculation $b): int => $way * ($a->registeredAt() <=> $b->registeredAt()),
        );

        if ($filter->limit > 0) {
            $results = array_slice($results, $filter->page * $filter->limit, $filter->limit);
        }

        return $results;
    }

    public function totalResultsByFilter(TripCalculationFilter $filter): int
    {
        $count = 0;

        foreach ($this->tripCalculations as $tripCalculation) {
            if ($this->matches($tripCalculation, $filter)) {
                ++$count;
            }
        }

        return $count;
    }

    /** @return list<TripCalculation> */
    public function findByLinkId(TripCalculationLinkId $tripCalculationLinkId): array
    {
        return $this->findByFilter(new TripCalculationFilter(tripCalculationLinkId: $tripCalculationLinkId, limit: 0));
    }

    private function matches(TripCalculation $tripCalculation, TripCalculationFilter $filter): bool
    {
        if (null !== $filter->tripCalculationLinkId) {
            $linkId = $tripCalculation->tripCalculationLinkId();

            if (!$linkId instanceof TripCalculationLinkId || !$linkId->equals($filter->tripCalculationLinkId)) {
                return false;
            }
        }

        if (null !== $filter->tripCalculationLinkIds) {
            $linkId = $tripCalculation->tripCalculationLinkId();

            if (!$linkId instanceof TripCalculationLinkId) {
                return false;
            }

            $linkIds = array_map(static fn(TripCalculationLinkId $tripCalculationLinkId): string => (string) $tripCalculationLinkId, $filter->tripCalculationLinkIds);

            if (!in_array((string) $linkId, $linkIds, true)) {
                return false;
            }
        }

        if (null !== $filter->from && $tripCalculation->registeredAt() < $filter->from) {
            return false;
        }

        return !(null !== $filter->to && $tripCalculation->registeredAt() > $filter->to);
    }
}
