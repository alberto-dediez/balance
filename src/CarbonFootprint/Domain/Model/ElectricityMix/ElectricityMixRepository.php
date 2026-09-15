<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ElectricityMix;

use App\Property\Domain\Model\Country\CountryId;

interface ElectricityMixRepository
{
    public function add(ElectricityMix $electricityMix): void;

    public function remove(ElectricityMix $electricityMix): void;

    public function find(ElectricityMixId $electricityMixId): ?ElectricityMix;

    public function findByCountryId(CountryId $countryId): ?ElectricityMix;

    /** @return array<ElectricityMix> */
    public function findByFilter(ElectricityMixFilter $filter): array;

    public function totalResultsByFilter(ElectricityMixFilter $filter): int;
}
