<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationMeasureCategory;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class MitigationMeasureCategoryTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private MitigationMeasureCategory $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): MitigationMeasureCategory
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof MitigationMeasureCategoryTranslation);

        $this->name = $translation->name();
    }
}
