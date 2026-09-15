<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryRefrigerantGasRepository;

class RefrigerantGasRepositoryMother
{
    public static function inMemory(): InMemoryRefrigerantGasRepository
    {
        return new InMemoryRefrigerantGasRepository();
    }
}
