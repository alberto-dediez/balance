<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasEmissionFactorId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class RefrigerantGasEmissionFactorIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'refrigerant_gas_emission_factor_id';
    }

    public function getClassName(): string
    {
        return RefrigerantGasEmissionFactorId::class;
    }
}
