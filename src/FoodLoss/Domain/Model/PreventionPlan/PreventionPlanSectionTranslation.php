<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class PreventionPlanSectionTranslation implements Translation
{
    public function __construct(
        private string $title,
        private string $content,
        private readonly Locale $locale,
        private readonly PreventionPlanSection $translatable,
    ) {}

    public function title(): string
    {
        return $this->title;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): PreventionPlanSection
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof PreventionPlanSectionTranslation);

        $this->title = $translation->title();
        $this->content = $translation->content();
    }
}
