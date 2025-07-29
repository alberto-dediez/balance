<?php

namespace App\Tests\Unit\Property\Infrastructure\Repository\Category;

use Doctrine\ORM\EntityManagerInterface;
use App\Property\Domain\Model\Category\CategoryRepository;
use App\Tests\Unit\Property\Domain\Model\Category\CategoryRepositoryTest;
use App\Property\Infrastructure\Repository\Category\DoctrineCategoryRepository;

class DoctrineCategoryRepositoryTest extends CategoryRepositoryTest
{
    public function createSUT(): CategoryRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineCategoryRepository($manager);
    }
}