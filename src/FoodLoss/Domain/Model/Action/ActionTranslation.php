<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Action;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class ActionTranslation implements Translation
{
    public function __construct(
        private string $description,
        private Locale $locale,
        private Action $translatable,
    ) {}

    public function description(): string
    {
        return $this->description;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): Action
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof ActionTranslation);

        $this->description = $translation->description();
    }
}
