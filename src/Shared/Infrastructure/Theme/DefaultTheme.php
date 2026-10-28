<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Theme;

use App\Security\Domain\Model\Branding\FontFamilyName;
use App\Security\Domain\Model\Branding\HexColor;
use App\Security\Domain\Model\Branding\PdfFontFamily;
use App\Shared\Domain\Model\Theme\Theme;
use App\Shared\Domain\Model\Theme\ThemeDefaults;

final class DefaultTheme implements Theme
{
    public const string SLUG = 'default';

    public function slug(): string
    {
        return self::SLUG;
    }

    public function name(): string
    {
        return 'Bioscore';
    }

    public function defaults(): ThemeDefaults
    {
        return new ThemeDefaults(
            primary: new HexColor('#3E7490'),
            secondary: new HexColor('#C3D300'),
            success: new HexColor('#00CEB6'),
            info: new HexColor('#46CAEB'),
            warning: new HexColor('#FFAE1F'),
            danger: new HexColor('#FF6692'),
            light: new HexColor('#DFE5EF'),
            dark: new HexColor('#111C2D'),
            bodyColor: new HexColor('#5A6A85'),
            bodyBg: new HexColor('#F8FAFD'),
            webFontFamily: new FontFamilyName('Inter'),
            webFontWeights: '300;400;500;600;700',
            pdfFontFamily: PdfFontFamily::Rubik,
        );
    }

    public function stylesheet(): ?string
    {
        return null;
    }

    public function logo(): ?string
    {
        return null;
    }

    public function inverseLogo(): ?string
    {
        return null;
    }
}
