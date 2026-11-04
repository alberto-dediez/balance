<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\DistributorAssignment;

use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryDistributorAssignmentRepository;

class DistributorAssignmentRepositoryMother
{
    /** @param list<DistributorAssignment> $assignments */
    public static function inMemory(array $assignments = []): InMemoryDistributorAssignmentRepository
    {
        $repository = new InMemoryDistributorAssignmentRepository();

        foreach ($assignments as $assignment) {
            $repository->add($assignment);
        }

        return $repository;
    }
}
