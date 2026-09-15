<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class RefrigerantGasIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'refrigerant_gas_id';
    }

    public function getClassName(): string
    {
        return RefrigerantGasId::class;
    }
}
