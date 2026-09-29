<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasure;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMitigationMeasureRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class MitigationMeasureRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryMitigationMeasureRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryMitigationMeasureRepository();
    }
}
