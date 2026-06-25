<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MerchandisingItem;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class MerchandisingItemTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private MerchandisingItem $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): MerchandisingItem
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof MerchandisingItemTranslation);

        $this->name = $translation->name();
    }
}
