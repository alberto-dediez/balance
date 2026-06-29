<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor;

use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryGdOFactorRepositoryTest extends GdOFactorRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): GdOFactorRepository
    {
        return GdOFactorRepositoryMother::inMemory($locale);
    }
}
