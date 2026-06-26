<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerEmissionFactorId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ElectricityMarketerEmissionFactorIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'electricity_marketer_emission_factor_id';
    }

    public function getClassName(): string
    {
        return ElectricityMarketerEmissionFactorId::class;
    }
}
