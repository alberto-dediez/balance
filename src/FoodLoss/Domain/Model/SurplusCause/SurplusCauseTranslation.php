<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\SurplusCause;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class SurplusCauseTranslation implements Translation
{
    public function __construct(
        private string $name,
        private readonly Locale $locale,
        private readonly SurplusCause $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): SurplusCause
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof SurplusCauseTranslation);

        $this->name = $translation->name();
    }
}
