<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\ProtocolAccess;

use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessRepository;

class InMemoryProtocolAccessRepositoryTest extends ProtocolAccessRepositoryTest
{
    protected function createSUT(): ProtocolAccessRepository
    {
        return ProtocolAccessRepositoryMother::inMemory();
    }
}
