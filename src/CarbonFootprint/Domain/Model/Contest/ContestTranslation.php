<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class ContestTranslation implements Translation
{
    public function __construct(
        private string $name,
        private string $description,
        private string $prizeDescription,
        private string $terms,
        private Locale $locale,
        private Contest $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function prizeDescription(): string
    {
        return $this->prizeDescription;
    }

    public function terms(): string
    {
        return $this->terms;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): Contest
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof ContestTranslation);

        $this->name = $translation->name();
        $this->description = $translation->description();
        $this->prizeDescription = $translation->prizeDescription();
        $this->terms = $translation->terms();
    }
}
