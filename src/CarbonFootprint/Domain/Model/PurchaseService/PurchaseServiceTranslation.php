<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PurchaseService;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class PurchaseServiceTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private PurchaseService $translatable,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): PurchaseService
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof PurchaseServiceTranslation);

        $this->name = $translation->name();
    }
}
