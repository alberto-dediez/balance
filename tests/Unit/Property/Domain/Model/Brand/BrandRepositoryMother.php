<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Brand;

use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryBrandRepository;

class BrandRepositoryMother
{
    public static function inMemory(): InMemoryBrandRepository
    {
        return new InMemoryBrandRepository();
    }
}
