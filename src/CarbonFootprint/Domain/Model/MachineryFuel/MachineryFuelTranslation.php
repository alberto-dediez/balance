<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MachineryFuel;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class MachineryFuelTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private MachineryFuel $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): MachineryFuel
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof MachineryFuelTranslation);

        $this->name = $translation->name();
    }
}
