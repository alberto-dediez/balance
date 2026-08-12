<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PublicLanding;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class PublicLandingTranslation implements Translation
{
    public function __construct(
        private string $title,
        private string $subtitle,
        private string $intro,
        private string $ctaLabel,
        private Locale $locale,
        private PublicLanding $translatable,
    ) {}

    public function title(): string
    {
        return $this->title;
    }

    public function subtitle(): string
    {
        return $this->subtitle;
    }

    public function intro(): string
    {
        return $this->intro;
    }

    public function ctaLabel(): string
    {
        return $this->ctaLabel;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): PublicLanding
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof PublicLandingTranslation);

        $this->title = $translation->title();
        $this->subtitle = $translation->subtitle();
        $this->intro = $translation->intro();
        $this->ctaLabel = $translation->ctaLabel();
    }
}
