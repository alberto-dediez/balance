<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Protocol;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryProtocolRepository;

class ProtocolRepositoryMother
{
    public static function inMemory(): InMemoryProtocolRepository
    {
        return new InMemoryProtocolRepository();
    }
}
