<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\AlertConfiguration;

use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryAlertConfigurationRepository;

class AlertConfigurationRepositoryMother
{
    public static function inMemory(): InMemoryAlertConfigurationRepository
    {
        return new InMemoryAlertConfigurationRepository();
    }
}
