<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Navigation;

final readonly class NavigationEntry
{
    public function __construct(
        public string $title,
        public ?string $subtitle,
        public string $url,
        public string $icon,
        public string $haystack,
    ) {}
}
