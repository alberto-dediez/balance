<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution;

class OrganizationalProviderContributionConsentRequiredException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Debes aceptar el tratamiento de tus datos para continuar.', 403);
    }
}
