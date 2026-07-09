<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ProviderContribution;

class ProviderContributionClosedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('La huella de carbono de este evento ya se ha cerrado y no admite más cambios.', 409);
    }
}
