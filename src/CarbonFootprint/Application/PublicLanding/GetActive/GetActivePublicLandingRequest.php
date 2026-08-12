<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PublicLanding\GetActive;

use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;

class GetActivePublicLandingRequest
{
    public function __construct(
        public PublicLandingOwnerType $ownerType,
        public string $ownerId,
    ) {}
}
