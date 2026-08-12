<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PublicLanding\Update;

use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLanding;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdatePublicLandingResponse implements CommandResponse
{
    public function __construct(
        public PublicLanding $publicLanding,
    ) {}
}
