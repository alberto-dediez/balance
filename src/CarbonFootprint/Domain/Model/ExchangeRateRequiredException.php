<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model;

final class ExchangeRateRequiredException extends \LogicException
{
    public function __construct(Unit $from, Unit $to)
    {
        parent::__construct(sprintf(
            'Converting between "%s" and "%s" requires an exchange rate; use UnitConverter.',
            $from->value,
            $to->value,
        ));
    }
}
