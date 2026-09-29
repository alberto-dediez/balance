<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialitySector;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class MaterialitySectorTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private MaterialitySector $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): MaterialitySector
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof MaterialitySectorTranslation);

        $this->name = $translation->name();
    }
}
