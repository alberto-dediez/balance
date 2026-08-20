<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Branding;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class BrandingExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private readonly BrandingContext $context) {}

    /**
     * @return array<string, mixed>
     */
    public function getGlobals(): array
    {
        return [
            'branding' => $this->context,
        ];
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('branding_css_vars', [BrandingRuntime::class, 'cssVariables']),
            new TwigFunction('branding_google_fonts_url', [BrandingRuntime::class, 'googleFontsUrl']),
            new TwigFunction('branding_logo_url', [BrandingRuntime::class, 'logoUrl']),
            new TwigFunction('branding_stylesheet', [BrandingRuntime::class, 'stylesheet']),
            new TwigFunction('theme_template', [BrandingRuntime::class, 'themeTemplate']),
        ];
    }
}
