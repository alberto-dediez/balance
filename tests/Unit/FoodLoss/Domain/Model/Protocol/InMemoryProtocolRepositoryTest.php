<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Protocol;

use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;

class InMemoryProtocolRepositoryTest extends ProtocolRepositoryTest
{
    protected function createSUT(): ProtocolRepository
    {
        return ProtocolRepositoryMother::inMemory();
    }
}
