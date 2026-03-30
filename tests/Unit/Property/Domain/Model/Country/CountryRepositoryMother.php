<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Country;

use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCountryRepository;

class CountryRepositoryMother
{
    public static function inMemory(): InMemoryCountryRepository
    {
        return new InMemoryCountryRepository();
    }
}
