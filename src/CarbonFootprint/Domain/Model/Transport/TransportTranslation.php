<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Transport;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class TransportTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private Transport $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): Transport
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof TransportTranslation);

        $this->name = $translation->name();
    }
}
