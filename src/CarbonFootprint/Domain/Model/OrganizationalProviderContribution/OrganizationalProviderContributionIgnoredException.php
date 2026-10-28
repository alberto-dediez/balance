<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution;

class OrganizationalProviderContributionIgnoredException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Esta contribución ha sido descartada.', 404);
    }
}
