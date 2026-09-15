<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMix;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixFilter;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\Property\Domain\Model\Country\CountryId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryElectricityMixRepository implements ElectricityMixRepository
{
    /** @param array<string, ElectricityMix> $electricityMixes */
    public function __construct(
        private array $electricityMixes = [],
    ) {}

    public function add(ElectricityMix $electricityMix): void
    {
        if (isset($this->electricityMixes[(string) $electricityMix->id()])) {
            return;
        }

        $this->electricityMixes[(string) $electricityMix->id()] = $electricityMix;
    }

    public function remove(ElectricityMix $electricityMix): void
    {
        if (!isset($this->electricityMixes[(string) $electricityMix->id()])) {
            return;
        }

        unset($this->electricityMixes[(string) $electricityMix->id()]);
    }

    public function find(ElectricityMixId $electricityMixId): ?ElectricityMix
    {
        return (isset($this->electricityMixes[(string) $electricityMixId]) && false == $this->electricityMixes[(string) $electricityMixId]->isRemoved())
            ? $this->electricityMixes[(string) $electricityMixId]
            : null
        ;
    }

    public function findByCountryId(CountryId $countryId): ?ElectricityMix
    {
        foreach ($this->electricityMixes as $electricityMix) {
            if ($electricityMix->isRemoved()) {
                continue;
            }

            if ((string) $electricityMix->countryId() === (string) $countryId) {
                return $electricityMix;
            }
        }

        return null;
    }

    /** @return array<int, ElectricityMix> */
    public function findByFilter(ElectricityMixFilter $filter): array
    {
        $results = [];

        if (0 === count($this->electricityMixes)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->electricityMixes as $electricityMix) {
            if (true == $electricityMix->isRemoved()) {
                continue;
            }

            if (
                null !== $filter->countryId
                && (string) $electricityMix->countryId() !== (string) $filter->countryId
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

            $results[] = $electricityMix;
        }

        return $results;
    }

    public function totalResultsByFilter(ElectricityMixFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
