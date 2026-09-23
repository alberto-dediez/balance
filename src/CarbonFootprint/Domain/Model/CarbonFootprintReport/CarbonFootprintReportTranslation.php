<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class CarbonFootprintReportTranslation implements Translation
{
    public function __construct(
        private string $filePath,
        private Locale $locale,
        private CarbonFootprintReport $translatable,
    ) {}

    public function filePath(): string
    {
        return $this->filePath;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): CarbonFootprintReport
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof CarbonFootprintReportTranslation);

        $this->filePath = $translation->filePath();
    }
}
