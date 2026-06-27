<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Waste;

use App\CarbonFootprint\Domain\Model\Waste\Waste;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;

trait GetWasteFromIdTrait
{
    private function getWasteFromId(string $wasteId, WasteRepository $repository): Waste
    {
        $waste = $repository->find(new WasteId($wasteId));

        if (null == $waste) {
            throw $this->createNotFoundException('Residuo no encontrado');
        }

        return $waste;
    }
}
