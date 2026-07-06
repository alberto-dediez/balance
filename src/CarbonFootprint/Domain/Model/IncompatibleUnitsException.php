<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model;

final class IncompatibleUnitsException extends \InvalidArgumentException
{
    public function __construct(Unit $from, Unit $to)
    {
        parent::__construct(sprintf(
            'Cannot convert from unit "%s" (%s) to unit "%s" (%s): incompatible magnitudes.',
            $from->value,
            $from->magnitude()->value,
            $to->value,
            $to->magnitude()->value,
        ));
    }
}
