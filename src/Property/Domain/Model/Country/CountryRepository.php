<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Country;

use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;

interface CountryRepository
{
    public function add(Country $country): void;

    public function remove(Country $country): void;

    public function find(CountryId $countryId): ?Country;

    public function findByName(string $name): ?Country;

    public function findByIso(string $iso): ?Country;

    public function findBySourceAndExternalId(ExternalSource $source, ExternalId $externalId): ?Country;

    public function findByFilter(CountryFilter $filter): array;

    public function totalResultsByFilter(CountryFilter $filter): int;
}
