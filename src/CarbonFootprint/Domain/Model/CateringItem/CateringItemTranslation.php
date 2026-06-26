<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CateringItem;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class CateringItemTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private CateringItem $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): CateringItem
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof CateringItemTranslation);

        $this->name = $translation->name();
    }
}
