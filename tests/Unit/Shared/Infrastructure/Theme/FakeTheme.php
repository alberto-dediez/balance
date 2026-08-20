<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Theme;

use App\Security\Domain\Model\Branding\FontFamilyName;
use App\Security\Domain\Model\Branding\HexColor;
use App\Security\Domain\Model\Branding\PdfFontFamily;
use App\Shared\Domain\Model\Theme\Theme;
use App\Shared\Domain\Model\Theme\ThemeDefaults;

class FakeTheme implements Theme
{
    public function slug(): string
    {
        return 'aurora';
    }

    public function name(): string
    {
        return 'Aurora';
    }

    public function defaults(): ThemeDefaults
    {
        return new ThemeDefaults(
            primary: new HexColor('#123456'),
            secondary: new HexColor('#654321'),
            success: new HexColor('#00AA00'),
            info: new HexColor('#00AAAA'),
            warning: new HexColor('#AAAA00'),
            danger: new HexColor('#AA0000'),
            light: new HexColor('#EEEEEE'),
            dark: new HexColor('#111111'),
            bodyColor: new HexColor('#222222'),
            bodyBg: new HexColor('#FAFAFA'),
            webFontFamily: new FontFamilyName('Lora'),
            webFontWeights: '400;700',
            pdfFontFamily: PdfFontFamily::Inter,
        );
    }

    public function stylesheet(): ?string
    {
        return 'styles/themes/aurora.css';
    }
}
