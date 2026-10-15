<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ExchangeRate;

use App\CarbonFootprint\Domain\Model\Unit;

interface ExchangeRateRepository
{
    public function add(ExchangeRate $rate): void;

    public function find(ExchangeRateId $id): ?ExchangeRate;

    public function findByCurrency(Unit $currency): ?ExchangeRate;

    /** @return list<ExchangeRate> */
    public function findByFilter(ExchangeRateFilter $filter): array;

    public function totalResultsByFilter(ExchangeRateFilter $filter): int;
}
