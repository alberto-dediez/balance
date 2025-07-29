<?php

namespace App\Property\Application\Country\Import;

class ImportCountriesResponse
{
    public function __construct(
        /** @var CountryResponse[] $countries */
        public array $countries = [], 
        public int $matched = 0
    ) {
        
    }
}