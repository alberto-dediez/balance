<?php

namespace App\Waste\Infrastructure\UI\Web\Admin\Waste;

use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteId;
use Symfony\Component\Translation\TranslatableMessage;
use App\Waste\Domain\Model\Waste\WasteRepository;

trait GetWasteFromIdTrait
{
    private function getWasteFromId(string $wasteId, WasteRepository $wasteRepository): Waste
    {
        $waste = $wasteRepository->find(new WasteId($wasteId));

        if (null == $waste) {
            throw $this->createNotFoundException(new TranslatableMessage("El residuo no existe"));
        }

        return $waste;
    }
}