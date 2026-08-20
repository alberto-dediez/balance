<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Theme;

use App\Security\Domain\Model\Branding\FontFamilyName;
use App\Security\Domain\Model\Branding\HexColor;
use App\Security\Domain\Model\Branding\PdfFontFamily;

final readonly class ThemeDefaults
{
    public function __construct(
        public HexColor $primary,
        public HexColor $secondary,
        public HexColor $success,
        public HexColor $info,
        public HexColor $warning,
        public HexColor $danger,
        public HexColor $light,
        public HexColor $dark,
        public HexColor $bodyColor,
        public HexColor $bodyBg,
        public ?FontFamilyName $webFontFamily = null,
        public ?string $webFontWeights = null,
        public PdfFontFamily $pdfFontFamily = PdfFontFamily::Rubik,
    ) {}
}
