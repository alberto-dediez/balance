<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationAction;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class MitigationActionTranslation implements Translation
{
    public function __construct(
        private string $name,
        private string $description,
        private Locale $locale,
        private MitigationAction $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): MitigationAction
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof MitigationActionTranslation);

        $this->name = $translation->name();
        $this->description = $translation->description();
    }
}
