<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CapitalGood;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class CapitalGoodTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private CapitalGood $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): CapitalGood
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof CapitalGoodTranslation);

        $this->name = $translation->name();
    }
}
