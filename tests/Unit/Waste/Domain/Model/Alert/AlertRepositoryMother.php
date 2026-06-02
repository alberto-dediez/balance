<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\Alert;

use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryAlertRepository;

class AlertRepositoryMother
{
    public static function inMemory(): InMemoryAlertRepository
    {
        return new InMemoryAlertRepository();
    }
}
