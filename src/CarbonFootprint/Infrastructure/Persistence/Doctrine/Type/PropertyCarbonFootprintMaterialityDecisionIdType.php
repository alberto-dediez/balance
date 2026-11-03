<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMaterialityDecisionId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class PropertyCarbonFootprintMaterialityDecisionIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'property_carbon_footprint_materiality_decision_id';
    }

    public function getClassName(): string
    {
        return PropertyCarbonFootprintMaterialityDecisionId::class;
    }
}
