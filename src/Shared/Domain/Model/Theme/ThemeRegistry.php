<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Theme;

interface ThemeRegistry
{
    /** @return list<Theme> */
    public function all(): array;

    public function get(string $slug): Theme;

    public function default(): Theme;
}
