<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\SurplusCause;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemorySurplusCauseRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class SurplusCauseRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemorySurplusCauseRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemorySurplusCauseRepository();
    }
}
