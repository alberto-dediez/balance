<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PublicLanding;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class PublicLandingBlockTranslation implements Translation
{
    public function __construct(
        private string $title,
        private string $text,
        private Locale $locale,
        private PublicLandingBlock $translatable,
    ) {}

    public function title(): string
    {
        return $this->title;
    }

    public function text(): string
    {
        return $this->text;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): PublicLandingBlock
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof PublicLandingBlockTranslation);

        $this->title = $translation->title();
        $this->text = $translation->text();
    }
}
