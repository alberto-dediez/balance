<?php

declare(strict_types=1);

namespace App\Shared\Application\Assistant;

final readonly class ToolPreview
{
    /** @param array<string, string> $displays */
    public function __construct(
        public array $displays = [],
    ) {}
}
