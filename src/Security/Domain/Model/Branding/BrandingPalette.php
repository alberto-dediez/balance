<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Branding;

use App\Library\Domain\Model\Media\MediaId;
use App\Shared\Domain\Model\Theme\ThemeDefaults;

final class BrandingPalette
{
    private const float HOVER_SHADE_WEIGHT = 0.15;
    private const float SUBTLE_TINT_WEIGHT = 0.80;

    public function resolve(?Branding $branding, ThemeDefaults $defaults, string $themeSlug): BrandingTokens
    {
        return $this->resolveOverrides(
            defaults: $defaults,
            themeSlug: $themeSlug,
            primary: $branding?->primary(),
            secondary: $branding?->secondary(),
            success: $branding?->success(),
            info: $branding?->info(),
            warning: $branding?->warning(),
            danger: $branding?->danger(),
            light: $branding?->light(),
            dark: $branding?->dark(),
            bodyColor: $branding?->bodyColor(),
            bodyBg: $branding?->bodyBg(),
            primaryHover: $branding?->primaryHover(),
            primarySubtle: $branding?->primarySubtle(),
            secondaryHover: $branding?->secondaryHover(),
            secondarySubtle: $branding?->secondarySubtle(),
            logoMediaId: $branding?->logoMediaId(),
            webFontFamily: $branding?->webFontFamily(),
            webFontWeights: $branding?->webFontWeights(),
            pdfFontFamily: $branding?->pdfFontFamily(),
            branded: $branding instanceof Branding && $branding->isCustomised(),
        );
    }

    public function resolveOverrides(
        ThemeDefaults $defaults,
        string $themeSlug,
        ?HexColor $primary,
        ?HexColor $secondary,
        ?HexColor $success,
        ?HexColor $info,
        ?HexColor $warning,
        ?HexColor $danger,
        ?HexColor $light,
        ?HexColor $dark,
        ?HexColor $bodyColor,
        ?HexColor $bodyBg,
        ?HexColor $primaryHover,
        ?HexColor $primarySubtle,
        ?HexColor $secondaryHover,
        ?HexColor $secondarySubtle,
        ?MediaId $logoMediaId,
        ?FontFamilyName $webFontFamily,
        ?string $webFontWeights,
        ?PdfFontFamily $pdfFontFamily,
        bool $branded,
    ): BrandingTokens {
        $resolvedPrimary = $primary ?? $defaults->primary;
        $resolvedSecondary = $secondary ?? $defaults->secondary;

        return new BrandingTokens(
            themeSlug: $themeSlug,
            primary: $resolvedPrimary,
            primaryHover: $primaryHover ?? $resolvedPrimary->shade(self::HOVER_SHADE_WEIGHT),
            primarySubtle: $primarySubtle ?? $resolvedPrimary->tint(self::SUBTLE_TINT_WEIGHT),
            secondary: $resolvedSecondary,
            secondaryHover: $secondaryHover ?? $resolvedSecondary->shade(self::HOVER_SHADE_WEIGHT),
            secondarySubtle: $secondarySubtle ?? $resolvedSecondary->tint(self::SUBTLE_TINT_WEIGHT),
            success: $success ?? $defaults->success,
            info: $info ?? $defaults->info,
            warning: $warning ?? $defaults->warning,
            danger: $danger ?? $defaults->danger,
            light: $light ?? $defaults->light,
            dark: $dark ?? $defaults->dark,
            bodyColor: $bodyColor ?? $defaults->bodyColor,
            bodyBg: $bodyBg ?? $defaults->bodyBg,
            pdfFontFamily: $pdfFontFamily ?? $defaults->pdfFontFamily,
            webFontFamily: $webFontFamily ?? $defaults->webFontFamily,
            webFontWeights: $webFontFamily instanceof FontFamilyName ? $webFontWeights : $defaults->webFontWeights,
            logoMediaId: $logoMediaId,
            branded: $branded,
        );
    }
}
