<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasureCategory;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMitigationMeasureCategoryRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class MitigationMeasureCategoryRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryMitigationMeasureCategoryRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryMitigationMeasureCategoryRepository();
    }
}
