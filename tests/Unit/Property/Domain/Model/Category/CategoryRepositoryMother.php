<?php

namespace App\Tests\Unit\Property\Domain\Model\Category;

use App\Property\Infrastructure\Repository\Category\InMemoryCategoryRepository;

class CategoryRepositoryMother
{
    public static function inMemory(): InMemoryCategoryRepository
    {
        return new InMemoryCategoryRepository();
    }
}