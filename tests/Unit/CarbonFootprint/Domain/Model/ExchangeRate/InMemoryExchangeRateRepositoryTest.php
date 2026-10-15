<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate;

use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRepository;

class InMemoryExchangeRateRepositoryTest extends ExchangeRateRepositoryTest
{
    protected function createSUT(): ExchangeRateRepository
    {
        return ExchangeRateRepositoryMother::inMemory();
    }
}
