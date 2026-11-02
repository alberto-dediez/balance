<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Infrastructure\Persistence\Doctrine\Repository;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessment;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRepository;
use App\Materiality\Infrastructure\Persistence\Doctrine\Repository\DoctrineMaterialityAssessmentRepository;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineMaterialityAssessmentRepositoryTest extends MaterialityAssessmentRepositoryTest
{
    protected function createSUT(): MaterialityAssessmentRepository
    {
        return new DoctrineMaterialityAssessmentRepository($this->manager());
    }

    protected function recover(MaterialityAssessmentRepository $repository, MaterialityAssessmentId $id): ?MaterialityAssessment
    {
        $this->manager()->clear();

        return $repository->find($id);
    }

    private function manager(): EntityManagerInterface
    {
        return $this->getContainer()->get(EntityManagerInterface::class);
    }
}
