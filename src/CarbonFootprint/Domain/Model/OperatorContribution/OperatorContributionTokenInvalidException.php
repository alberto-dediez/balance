<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OperatorContribution;

class OperatorContributionTokenInvalidException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('El enlace de aportación no es válido.', 404);
    }
}
