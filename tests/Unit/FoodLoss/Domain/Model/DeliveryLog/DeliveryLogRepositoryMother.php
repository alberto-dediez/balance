<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryDeliveryLogRepository;

class DeliveryLogRepositoryMother
{
    public static function inMemory(): InMemoryDeliveryLogRepository
    {
        return new InMemoryDeliveryLogRepository();
    }
}
