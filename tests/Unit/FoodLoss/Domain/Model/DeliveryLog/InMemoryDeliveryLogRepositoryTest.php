<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;

class InMemoryDeliveryLogRepositoryTest extends DeliveryLogRepositoryTest
{
    protected function createSUT(): DeliveryLogRepository
    {
        return DeliveryLogRepositoryMother::inMemory();
    }
}
