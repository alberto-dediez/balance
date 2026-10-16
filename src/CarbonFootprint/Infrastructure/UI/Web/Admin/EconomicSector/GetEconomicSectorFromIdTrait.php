<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\EconomicSector;

use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use Symfony\Component\Translation\TranslatableMessage;

trait GetEconomicSectorFromIdTrait
{
    private function getEconomicSectorFromId(string $economicSectorId, EconomicSectorRepository $repository): EconomicSector
    {
        $economicSector = $repository->find(new EconomicSectorId($economicSectorId));

        if (null == $economicSector) {
            throw $this->createNotFoundException((string) new TranslatableMessage('Sector económico no encontrado'));
        }

        return $economicSector;
    }
}
