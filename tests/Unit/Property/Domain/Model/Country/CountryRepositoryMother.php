<?php

namespace App\Tests\Unit\Property\Domain\Model\Country;

use App\Property\Infrastructure\Repository\Country\InMemoryCountryRepository;

class CountryRepositoryMother
{
    public static function inMemory(): InMemoryCountryRepository
    {
        return new InMemoryCountryRepository();
    }
}