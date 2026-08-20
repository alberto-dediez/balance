<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\UI\Email;

use App\Security\Infrastructure\UI\Email\BrandingEmailStylesheet;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingTokensMother;
use PHPUnit\Framework\TestCase;

class BrandingEmailStylesheetTest extends TestCase
{
    public function testItEmitsLiteralHexesBecauseInlineCssCannotResolveVariables(): void
    {
        $css = (new BrandingEmailStylesheet())(BrandingTokensMother::branded());

        $this->assertStringNotContainsString('var(', $css);
        $this->assertStringContainsString('#8A2432', $css);
    }

    public function testItBrandsLinksButtonsAndHighlights(): void
    {
        $css = (new BrandingEmailStylesheet())(BrandingTokensMother::branded());

        $this->assertMatchesRegularExpression('/\na \{\s*color: #8A2432;/', $css);
        $this->assertStringContainsString('background-color: #8A2432', $css);
        $this->assertStringContainsString('rgba(138, 36, 50, 0.2)', $css);
    }

    public function testItUsesTheBrandingFont(): void
    {
        $css = (new BrandingEmailStylesheet())(BrandingTokensMother::branded());

        $this->assertStringContainsString('font-family: "Poppins", sans-serif;', $css);
    }

    public function testItFallsBackToInterWithoutAWebFont(): void
    {
        $css = (new BrandingEmailStylesheet())(BrandingTokensMother::withoutWebFont());

        $this->assertStringContainsString('font-family: "Inter", sans-serif;', $css);
    }

    public function testButtonTextIsWhiteOnADarkBrandAndBlackOnALightOne(): void
    {
        $css = (new BrandingEmailStylesheet())(BrandingTokensMother::branded());

        $this->assertMatchesRegularExpression('/\.btn-primary \{\s*color: #ffffff;/', $css);
        $this->assertMatchesRegularExpression('/\.btn-secondary \{\s*color: #000000;/', $css);
    }

    public function testTheDefaultTokensReproduceThePlatformPalette(): void
    {
        $css = (new BrandingEmailStylesheet())(BrandingTokensMother::default());

        $this->assertStringContainsString('#3E7490', $css);
        $this->assertStringNotContainsString('#8A2432', $css);
    }
}
