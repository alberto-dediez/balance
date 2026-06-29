<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOvernightStayRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class OvernightStayRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryOvernightStayRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryOvernightStayRepository();
    }
}
