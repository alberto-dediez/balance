<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Persistence\InMemory\Repository;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Destination\Destination;
use App\Property\Domain\Model\Destination\DestinationFilter;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryDestinationRepository implements DestinationRepository
{
    /** @param Destination[] $destinations */
    public function __construct(private array $destinations = []) {}

    public function add(Destination $destination): void
    {
        if (isset($this->destinations[(string) $destination->id()])) {
            return;
        }

        $this->destinations[(string) $destination->id()] = $destination;
    }

    public function remove(Destination $destination): void
    {
        if (!isset($this->destinations[(string) $destination->id()])) {
            return;
        }

        unset($this->destinations[(string) $destination->id()]);
    }

    public function find(DestinationId $destinationId): ?Destination
    {
        return (isset($this->destinations[(string) $destinationId]) && false == $this->destinations[(string) $destinationId]->isRemoved())
            ? $this->destinations[(string) $destinationId]
            : null
        ;
    }

    public function findByNameAndCountry(string $name, CountryId $countryId): ?Destination
    {
        if (0 === count($this->destinations)) {
            return null;
        }

        foreach ($this->destinations as $destination) {
            if (strtolower($destination->name()) === strtolower($name) && null !== $destination->countryId() && $destination->countryId()->equals($countryId)) {
                return $this->find($destination->id());
            }
        }

        return null;
    }

    public function findBySourceAndExternalId(ExternalSource $source, ExternalId $externalId): ?Destination
    {
        if (0 === count($this->destinations)) {
            return null;
        }

        $sourceId = $source->id($externalId);
        $attribute = $source->attribute();

        foreach ($this->destinations as $destination) {
            if ($destination->externalId()->isEmpty()) {
                continue;
            }

            if ($destination->externalId()->$attribute() === $sourceId) {
                return $this->find($destination->id());
            }
        }

        return null;
    }

    /** @return Destination[] */
    public function findByFilter(DestinationFilter $filter): array
    {
        $results = [];

        if (0 === count($this->destinations)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->destinations as $destination) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $destination->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if ($filter->countryId instanceof CountryId && null !== $destination->countryId() && !$filter->countryId->equals($destination->countryId())) {
                continue;
            }

            if (true == $destination->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $destination;
        }

        return $results;
    }

    public function totalResultsByFilter(DestinationFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($filter));
    }
}
