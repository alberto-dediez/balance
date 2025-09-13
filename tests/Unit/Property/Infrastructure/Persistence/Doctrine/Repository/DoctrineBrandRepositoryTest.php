<?php

namespace App\Tests\Unit\Property\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\Brand\BrandRepository;
use App\Property\Infrastructure\Repository\Brand\DoctrineBrandRepository;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineBrandRepositoryTest extends BrandRepositoryTest
{
    public function createSUT(): BrandRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineBrandRepository($manager);
    }
}
