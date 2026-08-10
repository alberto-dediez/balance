<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\Persistence\Doctrine\Repository;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Infrastructure\Persistence\Doctrine\Repository\DoctrineProjectContributionRepository;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineProjectContributionRepositoryTest extends ProjectContributionRepositoryTest
{
    protected function createSUT(): ProjectContributionRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineProjectContributionRepository($manager);
    }
}
