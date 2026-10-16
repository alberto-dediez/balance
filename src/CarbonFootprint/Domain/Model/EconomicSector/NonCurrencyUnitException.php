<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EconomicSector;

use App\CarbonFootprint\Domain\Model\Unit;

final class NonCurrencyUnitException extends \InvalidArgumentException
{
    public function __construct(Unit $unit)
    {
        parent::__construct(sprintf('Unit "%s" is not a currency.', $unit->value));
    }
}
