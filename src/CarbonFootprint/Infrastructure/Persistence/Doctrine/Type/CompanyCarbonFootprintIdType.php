<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class CompanyCarbonFootprintIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'company_carbon_footprint_id';
    }

    public function getClassName(): string
    {
        return CompanyCarbonFootprintId::class;
    }
}
