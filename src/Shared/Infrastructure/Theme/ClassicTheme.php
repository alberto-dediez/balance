<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Theme;

use App\Shared\Domain\Model\Theme\Theme;
use App\Shared\Domain\Model\Theme\ThemeDefaults;

final class ClassicTheme implements Theme
{
    public const string SLUG = 'classic';

    public function __construct(private readonly DefaultTheme $defaultTheme) {}

    public function slug(): string
    {
        return self::SLUG;
    }

    public function name(): string
    {
        return 'Classic';
    }

    public function defaults(): ThemeDefaults
    {
        return $this->defaultTheme->defaults();
    }

    public function stylesheet(): ?string
    {
        return null;
    }
}
