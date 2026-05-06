<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class PreventionPlanVersionTranslation implements Translation
{
    public function __construct(
        private string $pdfPath,
        private readonly Locale $locale,
        private readonly PreventionPlanVersion $translatable,
    ) {}

    public function pdfPath(): string
    {
        return $this->pdfPath;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): PreventionPlanVersion
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof PreventionPlanVersionTranslation);

        $this->pdfPath = $translation->pdfPath();
    }
}
