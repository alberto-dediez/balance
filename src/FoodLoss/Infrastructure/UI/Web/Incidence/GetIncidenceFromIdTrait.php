<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Incidence;

use App\FoodLoss\Domain\Model\Incidence\Incidence;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;

trait GetIncidenceFromIdTrait
{
    private function getIncidenceFromId(string $incidenceId, IncidenceRepository $repository): Incidence
    {
        $incidence = $repository->find(new IncidenceId($incidenceId));

        if (null == $incidence) {
            throw $this->createNotFoundException('Incidencia no encontrada');
        }

        return $incidence;
    }
}
