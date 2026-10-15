<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryExchangeRateRepository;

class ExchangeRateRepositoryMother
{
    public static function inMemory(): InMemoryExchangeRateRepository
    {
        return new InMemoryExchangeRateRepository();
    }
}
