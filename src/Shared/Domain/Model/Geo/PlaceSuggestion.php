<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Geo;

final readonly class PlaceSuggestion
{
    public function __construct(
        public string $reference,
        public string $label,
        public ?string $secondaryLabel = null,
    ) {}
}
