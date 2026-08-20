<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\Branding;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Branding\BrandingPalette;
use App\Security\Domain\Model\Branding\FontFamilyName;
use App\Security\Domain\Model\Branding\HexColor;
use App\Security\Domain\Model\Branding\PdfFontFamily;
use App\Shared\Infrastructure\Theme\DefaultTheme;
use PHPUnit\Framework\TestCase;

class BrandingPaletteTest extends TestCase
{
    public function testWithoutBrandingItReturnsTheThemeDefaults(): void
    {
        $theme = new DefaultTheme();

        $tokens = (new BrandingPalette())->resolve(null, $theme->defaults(), $theme->slug());

        $this->assertSame('#3E7490', $tokens->primary->value);
        $this->assertSame('#C3D300', $tokens->secondary->value);
        $this->assertSame(DefaultTheme::SLUG, $tokens->themeSlug);
        $this->assertFalse($tokens->branded);
    }

    public function testDerivedTonesMatchBootstrapShadeAndTint(): void
    {
        $theme = new DefaultTheme();

        $tokens = (new BrandingPalette())->resolve(null, $theme->defaults(), $theme->slug());

        $this->assertSame('#35637A', $tokens->primaryHover->value);
        $this->assertSame('#D8E3E9', $tokens->primarySubtle->value);
        $this->assertSame('#A6B300', $tokens->secondaryHover->value);
    }

    public function testBrandingValuesWinOverTheThemeDefaults(): void
    {
        $theme = new DefaultTheme();

        $branding = BrandingMother::brandedForAccount(AccountId::generate());

        $tokens = (new BrandingPalette())->resolve($branding, $theme->defaults(), $theme->slug());

        $this->assertSame('#8A2432', $tokens->primary->value);
        $this->assertSame('#E8B04B', $tokens->secondary->value);
        $this->assertSame('#00CEB6', $tokens->success->value);
        $this->assertTrue($tokens->branded);
    }

    public function testDerivedTonesFollowTheBrandingPrimary(): void
    {
        $theme = new DefaultTheme();

        $branding = BrandingMother::brandedForAccount(AccountId::generate());

        $tokens = (new BrandingPalette())->resolve($branding, $theme->defaults(), $theme->slug());

        $this->assertTrue((new HexColor('#8A2432'))->shade(0.15)->equals($tokens->primaryHover));
        $this->assertTrue((new HexColor('#8A2432'))->tint(0.80)->equals($tokens->primarySubtle));
    }

    public function testExplicitOverrideWinsOverTheDerivedTone(): void
    {
        $theme = new DefaultTheme();

        $branding = BrandingMother::forAccount(AccountId::generate());
        $branding->change(
            logoMediaId: null,
            primary: new HexColor('#8A2432'),
            secondary: null,
            success: null,
            info: null,
            warning: null,
            danger: null,
            light: null,
            dark: null,
            bodyColor: null,
            bodyBg: null,
            primaryHover: new HexColor('#FF0000'),
            primarySubtle: null,
            secondaryHover: null,
            secondarySubtle: null,
            webFontFamily: null,
            webFontWeights: null,
            pdfFontFamily: PdfFontFamily::Rubik,
        );

        $tokens = (new BrandingPalette())->resolve($branding, $theme->defaults(), $theme->slug());

        $this->assertSame('#FF0000', $tokens->primaryHover->value);
    }

    public function testWebFontWeightsComeFromTheThemeWhenTheBrandingHasNoFont(): void
    {
        $theme = new DefaultTheme();

        $branding = BrandingMother::forAccount(AccountId::generate());

        $tokens = (new BrandingPalette())->resolve($branding, $theme->defaults(), $theme->slug());

        $this->assertInstanceOf(FontFamilyName::class, $tokens->webFontFamily);
        $this->assertSame('Inter', $tokens->webFontFamily->value);
        $this->assertSame('300;400;500;600;700', $tokens->webFontWeights);
    }

    public function testWebFontWeightsComeFromTheBrandingWhenItDeclaresAFont(): void
    {
        $theme = new DefaultTheme();

        $branding = BrandingMother::brandedForAccount(AccountId::generate());

        $tokens = (new BrandingPalette())->resolve($branding, $theme->defaults(), $theme->slug());

        $this->assertInstanceOf(FontFamilyName::class, $tokens->webFontFamily);
        $this->assertSame('Poppins', $tokens->webFontFamily->value);
        $this->assertSame('400;600;700', $tokens->webFontWeights);
    }

    public function testCssVariablesExposeEveryToken(): void
    {
        $theme = new DefaultTheme();

        $variables = (new BrandingPalette())->resolve(null, $theme->defaults(), $theme->slug())->cssVariables();

        $this->assertSame('#3E7490', $variables['--brand-primary']);
        $this->assertSame('62, 116, 144', $variables['--brand-primary-rgb']);
        $this->assertSame('"Inter", sans-serif', $variables['--brand-font-family']);
        $this->assertArrayHasKey('--brand-body-bg', $variables);
    }
}
