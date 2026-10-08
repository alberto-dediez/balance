<?php

declare(strict_types=1);

namespace App\Shared\Application\Search;

final readonly class SearchCategory
{
    public function __construct(
        public string $key,
        public string $label,
        public string $icon,
        public int $position,
    ) {}
}
