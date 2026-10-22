<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintNonConsolidatedPropertyId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class CompanyCarbonFootprintNonConsolidatedPropertyIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'company_carbon_footprint_non_consolidated_property_id';
    }

    public function getClassName(): string
    {
        return CompanyCarbonFootprintNonConsolidatedPropertyId::class;
    }
}
