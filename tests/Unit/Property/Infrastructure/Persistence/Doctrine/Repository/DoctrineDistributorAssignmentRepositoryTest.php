<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepository;
use App\Property\Infrastructure\Persistence\Doctrine\Repository\DoctrineDistributorAssignmentRepository;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineDistributorAssignmentRepositoryTest extends DistributorAssignmentRepositoryTest
{
    public function createSUT(): DistributorAssignmentRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineDistributorAssignmentRepository($manager);
    }
}
