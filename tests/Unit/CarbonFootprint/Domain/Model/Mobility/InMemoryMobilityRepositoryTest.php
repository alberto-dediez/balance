<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility;

use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryMobilityRepositoryTest extends MobilityRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): MobilityRepository
    {
        return MobilityRepositoryMother::inMemory($locale);
    }
}
