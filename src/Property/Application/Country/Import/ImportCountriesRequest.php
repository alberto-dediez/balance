<?php

namespace App\Property\Application\Country\Import;

class ImportCountriesRequest
{
    public function __construct(
        /** @var CountryRequest[] $countries */
        public array $countries,
    ) {

    }
}