<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Transport;

use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryTransportRepositoryTest extends TransportRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): TransportRepository
    {
        return TransportRepositoryMother::inMemory($locale);
    }
}
