<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class EventCarbonFootprintReportTranslation implements Translation
{
    public function __construct(
        private string $filePath,
        private Locale $locale,
        private EventCarbonFootprintReport $translatable,
    ) {}

    public function filePath(): string
    {
        return $this->filePath;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): EventCarbonFootprintReport
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof EventCarbonFootprintReportTranslation);

        $this->filePath = $translation->filePath();
    }
}
