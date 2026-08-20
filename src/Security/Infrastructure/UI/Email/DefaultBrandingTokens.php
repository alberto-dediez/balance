<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Email;

use App\Security\Domain\Model\Branding\BrandingPalette;
use App\Security\Domain\Model\Branding\BrandingTokens;
use App\Shared\Domain\Model\Theme\ThemeRegistry;

class DefaultBrandingTokens
{
    public function __construct(
        private readonly ThemeRegistry $themeRegistry,
        private readonly BrandingPalette $palette,
    ) {}

    public function __invoke(): BrandingTokens
    {
        $theme = $this->themeRegistry->default();

        return $this->palette->resolve(null, $theme->defaults(), $theme->slug());
    }
}
