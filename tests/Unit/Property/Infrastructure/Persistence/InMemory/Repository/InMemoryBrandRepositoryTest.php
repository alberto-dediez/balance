<?php

namespace App\Tests\Unit\Property\Infrastructure\Persistence\InMemory\Repository;

use App\Property\Domain\Model\Brand\BrandRepository;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandRepositoryTest;

class InMemoryBrandRepositoryTest extends BrandRepositoryTest
{
    public function createSUT(): BrandRepository
    {
        return BrandRepositoryMother::inMemory();
    }
}
