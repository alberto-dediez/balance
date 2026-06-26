<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class MobilityRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryMobilityRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryMobilityRepository();
    }
}
