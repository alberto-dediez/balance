<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Theme;

use App\Security\Domain\Model\Branding\FontFamilyName;
use App\Security\Domain\Model\Branding\HexColor;
use App\Security\Domain\Model\Branding\PdfFontFamily;
use App\Shared\Domain\Model\Theme\Theme;
use App\Shared\Domain\Model\Theme\ThemeDefaults;

final class AuroraTheme implements Theme
{
    public const string SLUG = 'aurora';

    public function slug(): string
    {
        return self::SLUG;
    }

    public function name(): string
    {
        return 'Aurora';
    }

    public function defaults(): ThemeDefaults
    {
        return new ThemeDefaults(
            primary: new HexColor('#5B2A86'),
            secondary: new HexColor('#F2B441'),
            success: new HexColor('#2FA97D'),
            info: new HexColor('#3E8FC1'),
            warning: new HexColor('#E8A33D'),
            danger: new HexColor('#C7452F'),
            light: new HexColor('#EDE7F3'),
            dark: new HexColor('#241035'),
            bodyColor: new HexColor('#3B3247'),
            bodyBg: new HexColor('#FBF9FD'),
            webFontFamily: new FontFamilyName('Manrope'),
            webFontWeights: '400;600;800',
            pdfFontFamily: PdfFontFamily::Inter,
        );
    }

    public function stylesheet(): string
    {
        return 'styles/themes/aurora.css';
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
