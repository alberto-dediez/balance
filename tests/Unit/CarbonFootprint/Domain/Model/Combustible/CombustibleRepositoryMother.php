<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCombustibleRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class CombustibleRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryCombustibleRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryCombustibleRepository();
    }
}
