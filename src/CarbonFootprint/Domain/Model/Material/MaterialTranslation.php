<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Material;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class MaterialTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private Material $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): Material
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof MaterialTranslation);

        $this->name = $translation->name();
    }
}
