<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\ProtocolAccess;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryProtocolAccessRepository;

class ProtocolAccessRepositoryMother
{
    public static function inMemory(): InMemoryProtocolAccessRepository
    {
        return new InMemoryProtocolAccessRepository();
    }
}
