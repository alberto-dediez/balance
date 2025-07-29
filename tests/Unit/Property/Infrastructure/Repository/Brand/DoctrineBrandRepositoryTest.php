<?php

namespace App\Tests\Unit\Property\Infrastructure\Repository\Brand;

use Doctrine\ORM\EntityManagerInterface;
use App\Property\Domain\Model\Brand\BrandRepository;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandRepositoryTest;
use App\Property\Infrastructure\Repository\Brand\DoctrineBrandRepository;

class DoctrineBrandRepositoryTest extends BrandRepositoryTest
{
    public function createSUT(): BrandRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineBrandRepository($manager);
    }
}