<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRepository;

class InMemoryMaterialityAssessmentRepositoryTest extends MaterialityAssessmentRepositoryTest
{
    protected function createSUT(): MaterialityAssessmentRepository
    {
        return MaterialityAssessmentRepositoryMother::inMemory();
    }
}
