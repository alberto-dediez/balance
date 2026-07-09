<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\AssignProviders;

use App\EventOperations\Domain\Model\Provider\ProviderId;

class AssignedProvider
{
    public function __construct(
        public ProviderId $providerId,
    ) {}
}
