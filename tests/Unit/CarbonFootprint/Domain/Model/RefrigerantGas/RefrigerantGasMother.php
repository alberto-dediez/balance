<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas;

use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;

class RefrigerantGasMother
{
    public static function registeredRefrigerantGas(): RefrigerantGas
    {
        return self::fromName('refrigerant-gas-name');
    }

    public static function fromName(string $name): RefrigerantGas
    {
        return self::from(
            name: $name,
            id: (string) RefrigerantGasId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): RefrigerantGas
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
    ): RefrigerantGas {
        $refrigerantGas = new RefrigerantGas(
            id: new RefrigerantGasId($id),
        );
        $refrigerantGas->register($name);

        return $refrigerantGas;
    }

    public static function random(): RefrigerantGas
    {
        return self::from(
            name: 'refrigerant-gas-' . rand(100, 999),
            id: (string) RefrigerantGasId::generate(),
        );
    }
}
