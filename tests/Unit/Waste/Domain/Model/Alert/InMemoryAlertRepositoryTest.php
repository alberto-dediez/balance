<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\Alert;

use App\Waste\Domain\Model\Alert\AlertRepository;

class InMemoryAlertRepositoryTest extends AlertRepositoryTest
{
    protected function createSUT(): AlertRepository
    {
        return AlertRepositoryMother::inMemory();
    }
}
