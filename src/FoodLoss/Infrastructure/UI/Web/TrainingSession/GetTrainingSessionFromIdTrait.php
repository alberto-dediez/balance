<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\TrainingSession;

use App\FoodLoss\Domain\Model\TrainingSession\TrainingSession;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;

trait GetTrainingSessionFromIdTrait
{
    private function getTrainingSessionFromId(string $trainingSessionId, TrainingSessionRepository $repository): TrainingSession
    {
        $trainingSession = $repository->find(new TrainingSessionId($trainingSessionId));

        if (null == $trainingSession) {
            throw $this->createNotFoundException('Sesión formativa no encontrada');
        }

        return $trainingSession;
    }
}
