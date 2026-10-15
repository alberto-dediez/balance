<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Process;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class ProcessTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private Process $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): Process
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof ProcessTranslation);

        $this->name = $translation->name();
    }
}
