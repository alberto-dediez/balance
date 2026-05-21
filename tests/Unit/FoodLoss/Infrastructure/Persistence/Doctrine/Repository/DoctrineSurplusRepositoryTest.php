<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository\DoctrineSurplusRepository;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineSurplusRepositoryTest extends SurplusRepositoryTest
{
    protected function createSUT(): SurplusRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineSurplusRepository($manager);
    }
}
