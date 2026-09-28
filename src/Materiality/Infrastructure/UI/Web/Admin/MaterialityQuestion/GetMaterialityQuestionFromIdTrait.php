<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\MaterialityQuestion;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;

trait GetMaterialityQuestionFromIdTrait
{
    private function getMaterialityQuestionFromId(string $materialityQuestionId, MaterialityQuestionRepository $repository): MaterialityQuestion
    {
        $materialityQuestion = $repository->find(new MaterialityQuestionId($materialityQuestionId));

        if (null == $materialityQuestion) {
            throw $this->createNotFoundException('Pregunta de materialidad no encontrada');
        }

        return $materialityQuestion;
    }
}
