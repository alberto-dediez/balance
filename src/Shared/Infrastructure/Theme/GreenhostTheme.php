<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Theme;

use App\Security\Domain\Model\Branding\FontFamilyName;
use App\Security\Domain\Model\Branding\HexColor;
use App\Security\Domain\Model\Branding\PdfFontFamily;
use App\Shared\Domain\Model\Theme\Theme;
use App\Shared\Domain\Model\Theme\ThemeDefaults;

final class GreenhostTheme implements Theme
{
    public const string SLUG = 'greenhost';

    public function slug(): string
    {
        return self::SLUG;
    }

    public function name(): string
    {
        return 'Greenhost';
    }

    public function defaults(): ThemeDefaults
    {
        return new ThemeDefaults(
            primary: new HexColor('#003432'),
            secondary: new HexColor('#668584'),
            success: new HexColor('#3BB662'),
            info: new HexColor('#435B6F'),
            warning: new HexColor('#E5C30F'),
            danger: new HexColor('#E75448'),
            light: new HexColor('#EEE4DA'),
            dark: new HexColor('#003432'),
            bodyColor: new HexColor('#30353A'),
            bodyBg: new HexColor('#F9F6F3'),
            webFontFamily: new FontFamilyName('Open Sans'),
            webFontWeights: '400;600;700',
            pdfFontFamily: PdfFontFamily::Inter,
        );
    }

    public function stylesheet(): string
    {
        return 'styles/themes/greenhost.css';
    }

    public function logo(): string
    {
        return 'images/logo-greenhost.png';
    }

    public function inverseLogo(): string
    {
        return 'images/logo-greenhost-white.png';
    }
}
