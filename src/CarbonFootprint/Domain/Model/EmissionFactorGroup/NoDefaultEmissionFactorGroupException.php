<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmissionFactorGroup;

class NoDefaultEmissionFactorGroupException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('No default emission factor group exists.');
    }
}
