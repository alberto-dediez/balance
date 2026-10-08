<?php

declare(strict_types=1);

namespace App\Shared\Application\Search;

final readonly class SearchResult
{
    /** @param array<string, string> $routeParameters */
    public function __construct(
        public string $title,
        public ?string $subtitle,
        public string $route,
        public array $routeParameters,
    ) {}
}
