<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport;

use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryRoadTransportRepositoryTest extends RoadTransportRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): RoadTransportRepository
    {
        return RoadTransportRepositoryMother::inMemory($locale);
    }
}
