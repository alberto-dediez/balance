<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\Branding;

use App\Library\Domain\Model\Media\MediaId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Branding\Branding;
use App\Security\Domain\Model\Branding\BrandingId;
use App\Security\Domain\Model\Branding\FontFamilyName;
use App\Security\Domain\Model\Branding\HexColor;
use App\Security\Domain\Model\Branding\PdfFontFamily;

class BrandingMother
{
    public static function forAccount(AccountId $accountId): Branding
    {
        $branding = new Branding(BrandingId::generate(), $accountId);
        $branding->register();

        return $branding;
    }

    public static function brandedForAccount(AccountId $accountId): Branding
    {
        $branding = self::forAccount($accountId);

        $branding->change(
            logoMediaId: MediaId::generate(),
            primary: new HexColor('#8A2432'),
            secondary: new HexColor('#E8B04B'),
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
            webFontFamily: new FontFamilyName('Poppins'),
            webFontWeights: '400;600;700',
            pdfFontFamily: PdfFontFamily::Rubik,
        );

        return $branding;
    }

    public static function random(): Branding
    {
        return self::forAccount(AccountId::generate());
    }
}
