<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Category;

use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCategoryRepository;

class CategoryRepositoryMother
{
    public static function inMemory(): InMemoryCategoryRepository
    {
        return new InMemoryCategoryRepository();
    }
}
