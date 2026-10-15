<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\UseProfile\EmissionFactor\CreateYear;

final class EmissionFactorYearAlreadyExistsException extends \DomainException
{
    public static function forYear(int $year): self
    {
        return new self(sprintf('Emission factors already exist for year %d', $year));
    }
}
