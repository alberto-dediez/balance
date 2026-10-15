<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\UseProfile;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class UseProfileTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private UseProfile $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): UseProfile
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof UseProfileTranslation);

        $this->name = $translation->name();
    }
}
