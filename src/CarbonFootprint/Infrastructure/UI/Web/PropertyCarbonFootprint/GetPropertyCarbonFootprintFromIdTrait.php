<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;

trait GetPropertyCarbonFootprintFromIdTrait
{
    private function getPropertyCarbonFootprintFromId(string $propertyCarbonFootprintId, PropertyCarbonFootprintRepository $repository): PropertyCarbonFootprint
    {
        $propertyCarbonFootprint = $repository->find(new PropertyCarbonFootprintId($propertyCarbonFootprintId));

        if (null === $propertyCarbonFootprint) {
            throw $this->createNotFoundException('El cálculo de huella de carbono no existe');
        }

        return $propertyCarbonFootprint;
    }
}
