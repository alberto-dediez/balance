<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment;

use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialityAssessmentRepository;

class MaterialityAssessmentRepositoryMother
{
    public static function inMemory(): InMemoryMaterialityAssessmentRepository
    {
        return new InMemoryMaterialityAssessmentRepository();
    }
}
