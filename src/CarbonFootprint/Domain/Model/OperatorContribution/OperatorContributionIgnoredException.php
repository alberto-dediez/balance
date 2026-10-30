<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OperatorContribution;

class OperatorContributionIgnoredException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Esta aportación ha sido descartada.', 404);
    }
}
