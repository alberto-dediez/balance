<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PublicLanding\Update;

use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingId;
use App\Library\Domain\Model\Media\MediaId;
use App\Shared\Application\CommandBus\Command;

class UpdatePublicLandingRequest implements Command
{
    /** @param PublicLandingBlockData[] $blocks */
    public function __construct(
        public PublicLandingId $publicLandingId,
        public bool $active = false,
        public string $title = '',
        public string $subtitle = '',
        public string $intro = '',
        public ?MediaId $heroImage = null,
        public string $ctaLabel = '',
        public array $blocks = [],
    ) {}
}
