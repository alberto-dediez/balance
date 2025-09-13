<?php

namespace App\Tests\Unit\Property\Infrastructure\Persistence\InMemory\Repository;

use App\Property\Domain\Model\Country\CountryRepository;
use App\Tests\Unit\Property\Domain\Model\Country\CountryRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Country\CountryRepositoryTest;

class InMemoryCountryRepositoryTest extends CountryRepositoryTest
{
    public function createSUT(): CountryRepository
    {
        return CountryRepositoryMother::inMemory();
    }
}
