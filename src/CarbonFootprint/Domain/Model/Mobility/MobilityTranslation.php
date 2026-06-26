<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Mobility;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class MobilityTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private Mobility $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): Mobility
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof MobilityTranslation);

        $this->name = $translation->name();
    }
}
