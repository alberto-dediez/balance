<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PublicLanding\Register;

use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLanding;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterPublicLandingResponse implements CommandResponse
{
    public function __construct(
        public PublicLanding $publicLanding,
    ) {}
}
