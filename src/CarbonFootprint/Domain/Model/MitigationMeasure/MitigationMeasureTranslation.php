<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationMeasure;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class MitigationMeasureTranslation implements Translation
{
    public function __construct(
        private string $name,
        private string $description,
        private Locale $locale,
        private MitigationMeasure $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): MitigationMeasure
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof MitigationMeasureTranslation);

        $this->name = $translation->name();
        $this->description = $translation->description();
    }
}
