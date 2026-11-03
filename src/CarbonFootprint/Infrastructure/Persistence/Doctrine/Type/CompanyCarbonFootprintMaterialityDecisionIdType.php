<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMaterialityDecisionId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class CompanyCarbonFootprintMaterialityDecisionIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'company_carbon_footprint_materiality_decision_id';
    }

    public function getClassName(): string
    {
        return CompanyCarbonFootprintMaterialityDecisionId::class;
    }
}
