<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmissionFactorGroup;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class EmissionFactorGroupTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private EmissionFactorGroup $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): EmissionFactorGroup
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof EmissionFactorGroupTranslation);

        $this->name = $translation->name();
    }
}
