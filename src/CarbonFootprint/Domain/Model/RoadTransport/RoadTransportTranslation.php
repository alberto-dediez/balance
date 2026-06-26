<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\RoadTransport;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class RoadTransportTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private RoadTransport $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): RoadTransport
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof RoadTransportTranslation);

        $this->name = $translation->name();
    }
}
