<?php

namespace App\Tests\Unit\Property\Infrastructure\Repository\Brand;

use App\Property\Domain\Model\Brand\BrandRepository;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandRepositoryTest;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandRepositoryMother;

class InMemoryBrandRepositoryTest extends BrandRepositoryTest
{
    public function createSUT(): BrandRepository
    {
        return BrandRepositoryMother::inMemory();
    }
}