<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrineContestRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineContestRepositoryTest extends ContestRepositoryTest
{
    protected function createSUT(): ContestRepository
    {
        return new DoctrineContestRepository($this->manager());
    }

    protected function clearPersistence(): void
    {
        $this->manager()->clear();
    }

    private function manager(): EntityManagerInterface
    {
        return $this->getContainer()->get(EntityManagerInterface::class);
    }
}
