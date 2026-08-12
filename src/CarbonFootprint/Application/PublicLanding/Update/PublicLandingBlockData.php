<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PublicLanding\Update;

use App\Library\Domain\Model\Media\MediaId;

class PublicLandingBlockData
{
    public function __construct(
        public ?MediaId $image = null,
        public string $title = '',
        public string $text = '',
    ) {}

    public function isEmpty(): bool
    {
        return null === $this->image && '' === trim($this->title) && '' === trim($this->text);
    }
}
