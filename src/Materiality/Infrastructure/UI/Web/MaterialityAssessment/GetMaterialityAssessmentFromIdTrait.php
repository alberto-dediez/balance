<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\MaterialityAssessment;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessment;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRepository;

trait GetMaterialityAssessmentFromIdTrait
{
    private function getMaterialityAssessmentFromId(string $materialityAssessmentId, MaterialityAssessmentRepository $repository): MaterialityAssessment
    {
        $materialityAssessment = $repository->find(new MaterialityAssessmentId($materialityAssessmentId));

        if (null == $materialityAssessment) {
            throw $this->createNotFoundException('Evaluación de materialidad no encontrada');
        }

        return $materialityAssessment;
    }
}
