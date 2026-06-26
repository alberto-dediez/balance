<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Combustible;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class CombustibleTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private Combustible $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): Combustible
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof CombustibleTranslation);

        $this->name = $translation->name();
    }
}
