<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\CriticalPoint;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;

trait GetCriticalPointFromIdTrait
{
    private function getCriticalPointFromId(string $criticalPointId, CriticalPointRepository $repository): CriticalPoint
    {
        $criticalPoint = $repository->find(new CriticalPointId($criticalPointId));

        if (null == $criticalPoint) {
            throw $this->createNotFoundException('Punto crítico no encontrado');
        }

        return $criticalPoint;
    }
}
