<?php

namespace App\Tests\Unit\Property\Infrastructure\Repository\Category;

use App\Property\Domain\Model\Category\CategoryRepository;
use App\Tests\Unit\Property\Domain\Model\Category\CategoryRepositoryTest;
use App\Tests\Unit\Property\Domain\Model\Category\CategoryRepositoryMother;

class InMemoryCategoryRepositoryTest extends CategoryRepositoryTest
{
    public function createSUT(): CategoryRepository
    {
        return CategoryRepositoryMother::inMemory();
    }
}