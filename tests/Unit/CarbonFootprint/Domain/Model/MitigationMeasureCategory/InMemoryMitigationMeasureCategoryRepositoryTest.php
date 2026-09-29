<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasureCategory;

use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryMitigationMeasureCategoryRepositoryTest extends MitigationMeasureCategoryRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): MitigationMeasureCategoryRepository
    {
        return MitigationMeasureCategoryRepositoryMother::inMemory($locale);
    }
}
