<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Admin\Waste;

use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Waste\WasteRepository;

trait GetWasteFromIdTrait
{
    private function getWasteFromId(string $wasteId, WasteRepository $wasteRepository): Waste
    {
        $waste = $wasteRepository->find(new WasteId($wasteId));

        if (null == $waste) {
            throw $this->createNotFoundException('El residuo no existe');
        }

        return $waste;
    }
}
