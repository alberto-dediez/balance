<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\Branding;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Branding\BrandingPalette;
use App\Security\Domain\Model\Branding\BrandingTokens;
use App\Shared\Domain\Model\Theme\ThemeDefaults;
use App\Shared\Infrastructure\Theme\DefaultTheme;

class BrandingTokensMother
{
    public static function default(): BrandingTokens
    {
        $theme = new DefaultTheme();

        return (new BrandingPalette())->resolve(null, $theme->defaults(), $theme->slug());
    }

    public static function withoutWebFont(): BrandingTokens
    {
        $theme = new DefaultTheme();

        $defaults = $theme->defaults();

        return (new BrandingPalette())->resolveOverrides(
            defaults: new ThemeDefaults(
                primary: $defaults->primary,
                secondary: $defaults->secondary,
                success: $defaults->success,
                info: $defaults->info,
                warning: $defaults->warning,
                danger: $defaults->danger,
                light: $defaults->light,
                dark: $defaults->dark,
                bodyColor: $defaults->bodyColor,
                bodyBg: $defaults->bodyBg,
            ),
            themeSlug: $theme->slug(),
            primary: null,
            secondary: null,
            success: null,
            info: null,
            warning: null,
            danger: null,
            light: null,
            dark: null,
            bodyColor: null,
            bodyBg: null,
            primaryHover: null,
            primarySubtle: null,
            secondaryHover: null,
            secondarySubtle: null,
            logoMediaId: null,
            webFontFamily: null,
            webFontWeights: null,
            pdfFontFamily: null,
            branded: false,
        );
    }

    public static function branded(): BrandingTokens
    {
        $theme = new DefaultTheme();

        return (new BrandingPalette())->resolve(
            BrandingMother::brandedForAccount(AccountId::generate()),
            $theme->defaults(),
            $theme->slug(),
        );
    }
}
