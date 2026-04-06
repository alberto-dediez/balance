<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Reason;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class ReasonTranslation implements Translation
{
    public function __construct(
        private string $description,
        private readonly Locale $locale,
        private readonly Reason $translatable,
    ) {}

    public function description(): string
    {
        return $this->description;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): Reason
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof ReasonTranslation);

        $this->description = $translation->description();
    }
}
