<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class PropertyCarbonFootprintIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'property_carbon_footprint_id';
    }

    public function getClassName(): string
    {
        return PropertyCarbonFootprintId::class;
    }
}
