<?php

namespace App\Tests\Unit\Property\Domain\Model\Brand;

use App\Property\Infrastructure\Repository\Brand\InMemoryBrandRepository;

class BrandRepositoryMother
{
    public static function inMemory(): InMemoryBrandRepository
    {
        return new InMemoryBrandRepository();
    }
}