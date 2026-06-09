<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\AlertCheckQueue;

use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryAlertCheckQueueRepository;

class AlertCheckQueueRepositoryMother
{
    public static function inMemory(): InMemoryAlertCheckQueueRepository
    {
        return new InMemoryAlertCheckQueueRepository();
    }
}
