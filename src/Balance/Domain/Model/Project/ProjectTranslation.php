<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\Project;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class ProjectTranslation implements Translation
{
    public function __construct(
        private string $name,
        private string $description,
        private Locale $locale,
        private Project $translatable,
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

    public function translatable(): Project
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof ProjectTranslation);

        $this->name = $translation->name();
        $this->description = $translation->description();
    }
}
