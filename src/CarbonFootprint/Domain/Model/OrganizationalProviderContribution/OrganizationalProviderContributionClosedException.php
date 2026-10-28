<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution;

class OrganizationalProviderContributionClosedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('La huella de carbono ya se ha cerrado y no admite más cambios.', 409);
    }
}
