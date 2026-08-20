<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Branding;

use App\Library\Domain\Model\Media\MediaId;

final readonly class BrandingTokens
{
    public function __construct(
        public string $themeSlug,
        public HexColor $primary,
        public HexColor $primaryHover,
        public HexColor $primarySubtle,
        public HexColor $secondary,
        public HexColor $secondaryHover,
        public HexColor $secondarySubtle,
        public HexColor $success,
        public HexColor $info,
        public HexColor $warning,
        public HexColor $danger,
        public HexColor $light,
        public HexColor $dark,
        public HexColor $bodyColor,
        public HexColor $bodyBg,
        public PdfFontFamily $pdfFontFamily,
        public ?FontFamilyName $webFontFamily = null,
        public ?string $webFontWeights = null,
        public ?MediaId $logoMediaId = null,
        public bool $branded = false,
    ) {}

    /** @return array<string, string> */
    public function cssVariables(): array
    {
        $variables = [
            '--brand-primary' => $this->primary->value,
            '--brand-primary-hover' => $this->primaryHover->value,
            '--brand-primary-subtle' => $this->primarySubtle->value,
            '--brand-primary-rgb' => implode(', ', $this->primary->toRgb()),
            '--brand-secondary' => $this->secondary->value,
            '--brand-secondary-hover' => $this->secondaryHover->value,
            '--brand-secondary-subtle' => $this->secondarySubtle->value,
            '--brand-secondary-rgb' => implode(', ', $this->secondary->toRgb()),
            '--brand-success' => $this->success->value,
            '--brand-success-rgb' => implode(', ', $this->success->toRgb()),
            '--brand-info' => $this->info->value,
            '--brand-info-rgb' => implode(', ', $this->info->toRgb()),
            '--brand-warning' => $this->warning->value,
            '--brand-warning-rgb' => implode(', ', $this->warning->toRgb()),
            '--brand-danger' => $this->danger->value,
            '--brand-danger-rgb' => implode(', ', $this->danger->toRgb()),
            '--brand-light' => $this->light->value,
            '--brand-light-rgb' => implode(', ', $this->light->toRgb()),
            '--brand-dark' => $this->dark->value,
            '--brand-dark-rgb' => implode(', ', $this->dark->toRgb()),
            '--brand-body-color' => $this->bodyColor->value,
            '--brand-body-color-rgb' => implode(', ', $this->bodyColor->toRgb()),
            '--brand-body-bg' => $this->bodyBg->value,
            '--brand-body-bg-rgb' => implode(', ', $this->bodyBg->toRgb()),
        ];

        if ($this->webFontFamily instanceof FontFamilyName) {
            $variables['--brand-font-family'] = sprintf('"%s", sans-serif', $this->webFontFamily->value);
        }

        return $variables;
    }
}
