<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\WasteManager;

use App\Waste\Domain\Model\WasteManager\WasteManager;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;
use Symfony\Component\Translation\TranslatableMessage;

trait GetWasteManagerFromIdTrait
{
    private function getWasteManagerFromId(string $wasteManagerId, WasteManagerRepository $wasteManagerRepository): WasteManager
    {
        $wasteManager = $wasteManagerRepository->find(new WasteManagerId($wasteManagerId));

        if (null == $wasteManager) {
            throw $this->createNotFoundException(new TranslatableMessage('El gestor de residuos no existe'));
        }

        return $wasteManager;
    }
}
