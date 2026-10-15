<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

final class InvalidAcquisitionYearException extends \InvalidArgumentException
{
    public function __construct(int $year, int $min, int $max)
    {
        parent::__construct(sprintf('Acquisition year "%d" must be between %d and %d.', $year, $min, $max));
    }
}
