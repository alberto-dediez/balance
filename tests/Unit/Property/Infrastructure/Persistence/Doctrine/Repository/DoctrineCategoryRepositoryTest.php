<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\Category\CategoryRepository;
use App\Property\Infrastructure\Persistence\Doctrine\Repository\DoctrineCategoryRepository;
use App\Tests\Unit\Property\Domain\Model\Category\CategoryRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineCategoryRepositoryTest extends CategoryRepositoryTest
{
    public function createSUT(): CategoryRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineCategoryRepository($manager);
    }
}
