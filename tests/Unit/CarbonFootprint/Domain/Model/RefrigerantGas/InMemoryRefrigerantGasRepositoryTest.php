<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas;

use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;

class InMemoryRefrigerantGasRepositoryTest extends RefrigerantGasRepositoryTest
{
    protected function createSUT(): RefrigerantGasRepository
    {
        return RefrigerantGasRepositoryMother::inMemory();
    }
}
