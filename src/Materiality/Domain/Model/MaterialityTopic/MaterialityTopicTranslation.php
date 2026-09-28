<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityTopic;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class MaterialityTopicTranslation implements Translation
{
    public function __construct(
        private string $name,
        private string $description,
        private Locale $locale,
        private MaterialityTopic $translatable,
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

    public function translatable(): MaterialityTopic
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof MaterialityTopicTranslation);

        $this->name = $translation->name();
        $this->description = $translation->description();
    }
}
