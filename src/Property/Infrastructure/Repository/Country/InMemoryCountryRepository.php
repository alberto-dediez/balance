<?php

namespace App\Property\Infrastructure\Repository\Country;

use App\Property\Domain\Model\Country\Country;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Country\CountryFilter;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Domain\Model\Country\CountryRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryCountryRepository implements CountryRepository
{
    public function __construct(private array $countries = [])
    {

    }

    public function add(Country $country): void
    {
        if (isset($this->countries[(string) $country->id()])) {
            return ;
        }

        $this->countries[(string) $country->id()] = $country;
    }

    public function remove(Country $country): void
    {
        if (!isset($this->countries[(string) $country->id()])) {
            return ;
        }

        unset($this->countries[(string) $country->id()]);
    }

    public function find(CountryId $countryId): ?Country
    {
        return (isset($this->countries[(string) $countryId]) && false == $this->countries[(string) $countryId]->isRemoved()) 
            ? $this->countries[(string) $countryId]
            : null
        ;
    }

    public function findByName(string $name): ?Country
    {
        if (count($this->countries) == 0) {
            return null;
        }

        foreach ($this->countries as $country) {
            if (null !== $country->name() && strtolower($country->name()) == strtolower($name)) {
                return $this->find($country->id());
            }
        }

        return null;
    }

    public function findByIso(string $iso): ?Country
    {
        if (count($this->countries) == 0) {
            return null;
        }

        foreach ($this->countries as $country) {
            if (null !== $country->iso() && strtolower($country->iso()) == strtolower($iso)) {
                return $this->find($country->id());
            }
        }

        return null;
    }

    public function findBySourceAndExternalId(ExternalSource $source, ExternalId $externalId): ?Country
    {
        if (count($this->countries) == 0) {
            return null;
        }

        $sourceId = $source->id($externalId);
        $attribute = $source->attribute();

        foreach ($this->countries as $country) {
            if ($country->externalId()->isEmpty()) {
                continue;
            }

            if ($country->externalId()->$attribute() === $sourceId) {
                return $this->find($country->id());
            }
        }

        return null;
    }

    public function findByFilter(CountryFilter $filter): array
    {
        $results = [];

        if (count($this->countries) == 0) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->countries as $country) {
            if (
                !empty($filter->name) 
                && false === strpos(strtolower($country->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (null !== $filter->iso && $filter->iso != $country->iso()) {
                continue;
            }

            if (true == $country->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                $offset--;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $country;
        }

        return $results;
    }

    public function totalResultsByFilter(CountryFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($filter));
    }
}