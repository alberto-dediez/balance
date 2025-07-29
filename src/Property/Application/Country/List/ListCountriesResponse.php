<?php

namespace App\Property\Application\Country\List;

class ListCountriesResponse
{
    public function __construct(public array $countries = [], public int $totalResults = 0)
    {
        
    }
}