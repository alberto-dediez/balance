<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixEmissionFactorId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ElectricityMixEmissionFactorIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'electricity_mix_emission_factor_id';
    }

    public function getClassName(): string
    {
        return ElectricityMixEmissionFactorId::class;
    }
}
