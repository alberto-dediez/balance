<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\AlertConfiguration;

use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;

class InMemoryAlertConfigurationRepositoryTest extends AlertConfigurationRepositoryTest
{
    protected function createSUT(): AlertConfigurationRepository
    {
        return AlertConfigurationRepositoryMother::inMemory();
    }
}
