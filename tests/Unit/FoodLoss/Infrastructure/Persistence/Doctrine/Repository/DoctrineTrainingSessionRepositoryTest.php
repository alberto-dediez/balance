<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository\DoctrineTrainingSessionRepository;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineTrainingSessionRepositoryTest extends TrainingSessionRepositoryTest
{
    protected function createSUT(): TrainingSessionRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineTrainingSessionRepository($manager);
    }
}
