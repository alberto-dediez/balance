<?php

declare(strict_types=1);

namespace App\Shared\Application\Assistant;

final readonly class ToolLink
{
    /** @param list<string> $parameters */
    public function __construct(
        public string $label,
        public string $route,
        public array $parameters = [],
    ) {}
}
