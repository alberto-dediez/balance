<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasure;

use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryMitigationMeasureRepositoryTest extends MitigationMeasureRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): MitigationMeasureRepository
    {
        return MitigationMeasureRepositoryMother::inMemory($locale);
    }
}
