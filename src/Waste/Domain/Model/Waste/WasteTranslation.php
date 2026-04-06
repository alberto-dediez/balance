<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Waste;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class WasteTranslation implements Translation
{
    public function __construct(
        private string $name,
        private readonly Locale $locale,
        private readonly Waste $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): Waste
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof WasteTranslation);

        $this->name = $translation->name();
    }
}
