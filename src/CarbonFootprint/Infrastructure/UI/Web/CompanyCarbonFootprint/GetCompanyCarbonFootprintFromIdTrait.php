<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;

trait GetCompanyCarbonFootprintFromIdTrait
{
    private function getCompanyCarbonFootprintFromId(string $companyCarbonFootprintId, CompanyCarbonFootprintRepository $repository): CompanyCarbonFootprint
    {
        $companyCarbonFootprint = $repository->find(new CompanyCarbonFootprintId($companyCarbonFootprintId));

        if (null === $companyCarbonFootprint) {
            throw $this->createNotFoundException('El cálculo de huella de carbono no existe');
        }

        return $companyCarbonFootprint;
    }
}
