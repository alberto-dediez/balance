<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PublicLanding\GetActive;

use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLanding;
use App\Library\Domain\Model\Media\Media;

class GetActivePublicLandingResponse
{
    /** @param array<string, Media> $images indexed by media id */
    public function __construct(
        public ?PublicLanding $publicLanding = null,
        public array $images = [],
    ) {}
}
