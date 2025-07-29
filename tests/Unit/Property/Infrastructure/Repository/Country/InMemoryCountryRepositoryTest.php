<?php

namespace App\Tests\Unit\Property\Infrastructure\Repository\Country;

use App\Property\Domain\Model\Country\CountryRepository;
use App\Tests\Unit\Property\Domain\Model\Country\CountryRepositoryTest;
use App\Tests\Unit\Property\Domain\Model\Country\CountryRepositoryMother;

class InMemoryCountryRepositoryTest extends CountryRepositoryTest
{
    public function createSUT(): CountryRepository
    {
        return CountryRepositoryMother::inMemory();
    }
}