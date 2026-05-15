<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\TrainingAttendance;

use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendance;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceId;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;

trait GetTrainingAttendanceFromIdTrait
{
    private function getTrainingAttendanceFromId(string $trainingAttendanceId, TrainingAttendanceRepository $repository): TrainingAttendance
    {
        $attendance = $repository->find(new TrainingAttendanceId($trainingAttendanceId));

        if (null === $attendance) {
            throw $this->createNotFoundException('Asistencia a formación no encontrada');
        }

        return $attendance;
    }
}
