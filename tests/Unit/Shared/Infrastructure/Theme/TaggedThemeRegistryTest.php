<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Theme;

use App\Shared\Infrastructure\Theme\DefaultTheme;
use PHPUnit\Framework\TestCase;

class TaggedThemeRegistryTest extends TestCase
{
    public function testUnknownSlugFallsBackToDefault(): void
    {
        $sut = ThemeRegistryMother::withThemes();

        $this->assertSame(DefaultTheme::SLUG, $sut->get('nope')->slug());
    }

    public function testDefaultThemeIsAlwaysRegistered(): void
    {
        $sut = ThemeRegistryMother::withThemes();

        $this->assertCount(1, $sut->all());
        $this->assertSame(DefaultTheme::SLUG, $sut->default()->slug());
    }

    public function testRegisteredThemeIsReturnedBySlug(): void
    {
        $theme = new FakeTheme();

        $sut = ThemeRegistryMother::withThemes([$theme]);

        $this->assertSame($theme, $sut->get($theme->slug()));
        $this->assertCount(2, $sut->all());
    }
}
