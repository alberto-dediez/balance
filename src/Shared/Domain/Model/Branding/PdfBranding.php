<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Branding;

use App\Security\Domain\Model\Branding\BrandingTokens;
use App\Security\Domain\Model\Branding\HexColor;
use App\Security\Domain\Model\Branding\PdfFontFamily;

final readonly class PdfBranding
{
    /**
     * @param array{0: int, 1: int, 2: int} $primary
     * @param array{0: int, 1: int, 2: int} $secondary
     * @param array{0: int, 1: int, 2: int} $dark
     * @param array{0: int, 1: int, 2: int} $darkShade
     * @param array{0: int, 1: int, 2: int} $lightShade
     * @param array{0: int, 1: int, 2: int} $subtle
     * @param array{0: int, 1: int, 2: int} $bodyText
     * @param array{0: int, 1: int, 2: int} $faintLine
     */
    public function __construct(
        public array $primary,
        public array $secondary,
        public array $dark,
        public array $darkShade,
        public array $lightShade,
        public array $subtle,
        public array $bodyText,
        public array $faintLine,
        public PdfFontFamily $fontFamily,
        public string $documentAuthor,
        public string $themeSlug = 'default',
        public ?string $logoPath = null,
        public ?string $themeLogo = null,
        public ?string $themeInverseLogo = null,
    ) {}

    public static function bioscore(?string $documentAuthor = null): self
    {
        return new self(
            primary: [64, 115, 142],
            secondary: [193, 209, 24],
            dark: [31, 72, 88],
            darkShade: [44, 90, 108],
            lightShade: [75, 120, 141],
            subtle: [244, 239, 224],
            bodyText: [55, 65, 75],
            faintLine: [220, 225, 230],
            fontFamily: PdfFontFamily::Rubik,
            documentAuthor: $documentAuthor ?? 'Bioscore Sustainability',
        );
    }

    public static function fromTokens(BrandingTokens $tokens, string $documentAuthor, ?string $logoPath = null): self
    {
        if (!$tokens->branded) {
            return self::bioscore($documentAuthor)->withLogo($logoPath);
        }


        return new self(
            primary: $tokens->primary->toRgb(),
            secondary: $tokens->secondary->toRgb(),
            dark: $tokens->primary->shade(0.45)->toRgb(),
            darkShade: $tokens->primary->shade(0.30)->toRgb(),
            lightShade: $tokens->primary->shade(0.15)->toRgb(),
            subtle: $tokens->primary->tint(0.92)->toRgb(),
            bodyText: $tokens->bodyColor->toRgb(),
            faintLine: $tokens->bodyColor->tint(0.85)->toRgb(),
            fontFamily: $tokens->pdfFontFamily,
            documentAuthor: $documentAuthor,
            themeSlug: $tokens->themeSlug,
            logoPath: $logoPath,
        );
    }

    public function withLogo(?string $logoPath): self
    {
        return new self(
            primary: $this->primary,
            secondary: $this->secondary,
            dark: $this->dark,
            darkShade: $this->darkShade,
            lightShade: $this->lightShade,
            subtle: $this->subtle,
            bodyText: $this->bodyText,
            faintLine: $this->faintLine,
            fontFamily: $this->fontFamily,
            documentAuthor: $this->documentAuthor,
            themeSlug: $this->themeSlug,
            logoPath: $logoPath,
            themeLogo: $this->themeLogo,
            themeInverseLogo: $this->themeInverseLogo,
        );
    }

    public function withThemeLogos(?string $themeLogo, ?string $themeInverseLogo): self
    {
        return new self(
            primary: $this->primary,
            secondary: $this->secondary,
            dark: $this->dark,
            darkShade: $this->darkShade,
            lightShade: $this->lightShade,
            subtle: $this->subtle,
            bodyText: $this->bodyText,
            faintLine: $this->faintLine,
            fontFamily: $this->fontFamily,
            documentAuthor: $this->documentAuthor,
            themeSlug: $this->themeSlug,
            logoPath: $this->logoPath,
            themeLogo: $themeLogo,
            themeInverseLogo: $themeInverseLogo,
        );
    }

    public function primaryHex(): string
    {
        return $this->hex($this->primary);
    }

    public function secondaryHex(): string
    {
        return $this->hex($this->secondary);
    }

    public function darkHex(): string
    {
        return $this->hex($this->dark);
    }

    public function subtleHex(): string
    {
        return $this->hex($this->subtle);
    }

    public function bodyTextHex(): string
    {
        return $this->hex($this->bodyText);
    }

    /** @param array{0: int, 1: int, 2: int} $rgb */
    private function hex(array $rgb): string
    {
        return (string) HexColor::fromRgb($rgb);
    }
}
