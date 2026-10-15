<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCapitalGoodRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class CapitalGoodRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryCapitalGoodRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryCapitalGoodRepository();
    }
}
