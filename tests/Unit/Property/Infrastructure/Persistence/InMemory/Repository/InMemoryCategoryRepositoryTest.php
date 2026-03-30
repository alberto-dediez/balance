<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Infrastructure\Persistence\InMemory\Repository;

use App\Property\Domain\Model\Category\CategoryRepository;
use App\Tests\Unit\Property\Domain\Model\Category\CategoryRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Category\CategoryRepositoryTest;

class InMemoryCategoryRepositoryTest extends CategoryRepositoryTest
{
    public function createSUT(): CategoryRepository
    {
        return CategoryRepositoryMother::inMemory();
    }
}
