<?php

namespace App\FoodLoss\Domain\Model\ServiceCategory;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class ServiceCategoryTranslation implements Translation
{
    public function __construct(
        private string $name,
        private Locale $locale,
        private ServiceCategoryId $translatableId
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

    public function translatableId(): ServiceCategoryId
    {
        return $this->translatableId;
    }
}