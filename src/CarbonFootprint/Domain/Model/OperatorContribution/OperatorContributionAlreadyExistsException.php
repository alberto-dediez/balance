<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OperatorContribution;

class OperatorContributionAlreadyExistsException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Esta propiedad ya tiene una invitación activa', 409);
    }
}
