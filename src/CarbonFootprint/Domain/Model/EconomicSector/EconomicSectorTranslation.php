<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EconomicSector;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class EconomicSectorTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private EconomicSector $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): EconomicSector
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof EconomicSectorTranslation);

        $this->name = $translation->name();
    }
}
