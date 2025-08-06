<?php

namespace App\Waste\Domain\Model\Waste;

use App\Waste\Domain\Model\Waste\WasteId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class WasteTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private WasteId|string $translatableId
    )
    {
        
    }

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatableId(): WasteId
    {
        if (is_string($this->translatableId)) {
            $this->translatableId = new WasteId($this->translatableId);
        }

        return $this->translatableId;
    }
}