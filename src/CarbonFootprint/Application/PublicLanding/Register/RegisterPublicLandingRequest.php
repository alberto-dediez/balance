<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PublicLanding\Register;

use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
use App\Shared\Application\CommandBus\Command;

class RegisterPublicLandingRequest implements Command
{
    public function __construct(
        public PublicLandingOwnerType $ownerType,
        public string $ownerId,
    ) {}
}
