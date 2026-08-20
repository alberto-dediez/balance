<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Email;

use App\Security\Domain\Model\Branding\BrandingTokens;
use App\Security\Domain\Model\Branding\FontFamilyName;
use App\Security\Domain\Model\Branding\HexColor;

class BrandingEmailStylesheet
{
    private const string FALLBACK_FONT = '"Inter", sans-serif';

    public function __invoke(BrandingTokens $tokens): string
    {
        $fontFamily = $tokens->webFontFamily instanceof FontFamilyName
            ? sprintf('"%s", sans-serif', $tokens->webFontFamily->value)
            : self::FALLBACK_FONT;

        return <<<CSS
            body {
              font-family: {$fontFamily};
              color: {$tokens->bodyColor};
            }
            a {
              color: {$tokens->primary};
            }
            .btn {
              font-family: {$fontFamily};
              color: {$tokens->bodyColor};
            }
            .btn-primary {
              color: {$this->readableOn($tokens->primary)};
              background-color: {$tokens->primary};
              border-color: {$tokens->primary};
            }
            .btn-secondary {
              color: {$this->readableOn($tokens->secondary)};
              background-color: {$tokens->secondary};
              border-color: {$tokens->secondary};
            }
            .h1, .h2, .h3, .h4, .h5, .h6, h1, h2, h3, h4, h5, h6 {
              font-family: {$fontFamily};
              color: {$tokens->dark};
            }
            th.highlighted, tr.highlighted, td.highlighted {
              background-color: {$this->translucent($tokens->primary)};
            }
            th.highlighted-secondary, tr.highlighted-secondary, td.highlighted-secondary {
              background-color: {$this->translucent($tokens->secondary)};
            }
            th.highlighted-terciary, tr.highlighted-terciary, td.highlighted-terciary {
              background-color: {$this->translucent($tokens->success)};
            }
            .top {
              background: {$tokens->light};
            }
            CSS;
    }

    private function readableOn(HexColor $background): string
    {
        $white = new HexColor('#FFFFFF');

        return $white->contrastRatio($background) >= (new HexColor('#000000'))->contrastRatio($background)
            ? '#ffffff'
            : '#000000';
    }

    private function translucent(HexColor $color): string
    {
        return sprintf('rgba(%d, %d, %d, 0.2)', ...$color->toRgb());
    }
}
