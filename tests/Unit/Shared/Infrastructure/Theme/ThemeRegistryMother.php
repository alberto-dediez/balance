<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Theme;

use App\Shared\Domain\Model\Theme\Theme;
use App\Shared\Infrastructure\Theme\DefaultTheme;
use App\Shared\Infrastructure\Theme\TaggedThemeRegistry;
use Psr\Log\NullLogger;

class ThemeRegistryMother
{
    /**
     * @param list<Theme> $themes
     */
    public static function withThemes(array $themes = []): TaggedThemeRegistry
    {
        return new TaggedThemeRegistry($themes, new NullLogger(), new DefaultTheme());
    }
}
