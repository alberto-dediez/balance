<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository\DoctrinePreventionPlanRepository;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrinePreventionPlanRepositoryTest extends PreventionPlanRepositoryTest
{
    protected function createSUT(): PreventionPlanRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrinePreventionPlanRepository($manager);
    }
}
