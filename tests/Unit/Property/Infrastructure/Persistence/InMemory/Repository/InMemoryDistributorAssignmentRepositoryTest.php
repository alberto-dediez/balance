<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Infrastructure\Persistence\InMemory\Repository;

use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepository;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepositoryTest;

class InMemoryDistributorAssignmentRepositoryTest extends DistributorAssignmentRepositoryTest
{
    public function createSUT(): DistributorAssignmentRepository
    {
        return DistributorAssignmentRepositoryMother::inMemory();
    }
}
