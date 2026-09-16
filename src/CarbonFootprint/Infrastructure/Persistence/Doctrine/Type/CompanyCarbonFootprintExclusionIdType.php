<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintExclusionId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class CompanyCarbonFootprintExclusionIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'company_carbon_footprint_exclusion_id';
    }

    public function getClassName(): string
    {
        return CompanyCarbonFootprintExclusionId::class;
    }
}
