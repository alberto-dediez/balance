<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\WasteManager;

use App\FoodLoss\Domain\Model\WasteManager\WasteManager;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerId;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerRepository;

trait GetWasteManagerFromIdTrait
{
    private function getWasteManagerFromId(string $wasteManagerId, WasteManagerRepository $repository): WasteManager
    {
        $wasteManager = $repository->find(new WasteManagerId($wasteManagerId));

        if (null == $wasteManager) {
            throw $this->createNotFoundException('Gestor de residuos no encontrado');
        }

        return $wasteManager;
    }
}
