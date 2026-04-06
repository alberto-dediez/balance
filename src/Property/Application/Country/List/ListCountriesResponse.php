<?php

declare(strict_types=1);

namespace App\Property\Application\Country\List;

use App\Property\Domain\Model\Country\Country;

class ListCountriesResponse
{
    /**
     * @param Country[] $countries
     */
    public function __construct(public array $countries = [], public int $totalResults = 0) {}
}
