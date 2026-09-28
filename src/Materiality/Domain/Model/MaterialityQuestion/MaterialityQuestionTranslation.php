<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityQuestion;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class MaterialityQuestionTranslation implements Translation
{
    public function __construct(
        private string $text,
        private string $help,
        private Locale $locale,
        private MaterialityQuestion $translatable,
    ) {}

    public function text(): string
    {
        return $this->text;
    }

    public function help(): string
    {
        return $this->help;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): MaterialityQuestion
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof MaterialityQuestionTranslation);

        $this->text = $translation->text();
        $this->help = $translation->help();
    }
}
