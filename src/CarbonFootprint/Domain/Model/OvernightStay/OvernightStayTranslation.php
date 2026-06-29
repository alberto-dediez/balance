<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OvernightStay;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class OvernightStayTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private OvernightStay $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): OvernightStay
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof OvernightStayTranslation);

        $this->name = $translation->name();
    }
}
