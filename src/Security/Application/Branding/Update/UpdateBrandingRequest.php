<?php

declare(strict_types=1);

namespace App\Security\Application\Branding\Update;

use App\Library\Domain\Model\Media\MediaId;
use App\Security\Domain\Model\Branding\BrandingId;
use App\Security\Domain\Model\Branding\FontFamilyName;
use App\Security\Domain\Model\Branding\HexColor;
use App\Security\Domain\Model\Branding\PdfFontFamily;
use App\Shared\Application\CommandBus\Command;

class UpdateBrandingRequest implements Command
{
    public function __construct(
        public BrandingId $brandingId,
        public ?MediaId $logoMediaId = null,
        public ?HexColor $primary = null,
        public ?HexColor $secondary = null,
        public ?HexColor $success = null,
        public ?HexColor $info = null,
        public ?HexColor $warning = null,
        public ?HexColor $danger = null,
        public ?HexColor $light = null,
        public ?HexColor $dark = null,
        public ?HexColor $bodyColor = null,
        public ?HexColor $bodyBg = null,
        public ?HexColor $primaryHover = null,
        public ?HexColor $primarySubtle = null,
        public ?HexColor $secondaryHover = null,
        public ?HexColor $secondarySubtle = null,
        public ?FontFamilyName $webFontFamily = null,
        public ?string $webFontWeights = null,
        public PdfFontFamily $pdfFontFamily = PdfFontFamily::Rubik,
    ) {}
}
