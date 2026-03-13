<?php

namespace App\FoodLoss\Domain\Model\Action;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class ActionTranslation implements Translation
{
    public function __construct(
        private string $description,
        private Locale $locale,
        private ActionId $translatableId
    )
    {
        
    }

    public function description(): string
    {
        return $this->description;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatableId(): ActionId
    {
        return $this->translatableId;
    }
}