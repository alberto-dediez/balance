<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Action;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryActionRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class ActionRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryActionRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryActionRepository();
    }
}
