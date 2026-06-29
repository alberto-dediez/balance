<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\GdOFactor;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class GdOFactorTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private GdOFactor $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): GdOFactor
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof GdOFactorTranslation);

        $this->name = $translation->name();
    }
}
