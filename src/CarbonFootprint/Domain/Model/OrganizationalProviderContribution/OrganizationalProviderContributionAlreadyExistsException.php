<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution;

class OrganizationalProviderContributionAlreadyExistsException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Ya existe una aportación de este proveedor para esta huella', 409);
    }
}
