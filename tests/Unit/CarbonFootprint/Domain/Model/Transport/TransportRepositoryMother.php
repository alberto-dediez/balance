<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Transport;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTransportRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class TransportRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryTransportRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryTransportRepository();
    }
}
