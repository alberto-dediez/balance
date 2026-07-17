<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmployeeContribution;

class EmployeeContributionIgnoredException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Esta contribución ha sido descartada.', 404);
    }
}
