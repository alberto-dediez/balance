<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Reason;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryReasonRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class ReasonRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryReasonRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryReasonRepository();
    }
}
