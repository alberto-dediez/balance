<?php

declare(strict_types=1);

namespace App\Property\Application\Country\List;

class ListCountriesResponse
{
    /**
     * @param \App\Property\Domain\Model\Country\Country[] $countries
     */
    public function __construct(public array $countries = [], public int $totalResults = 0) {}
}
