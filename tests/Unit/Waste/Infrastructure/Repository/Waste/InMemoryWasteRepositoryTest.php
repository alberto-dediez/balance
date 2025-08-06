<?php

namespace App\Tests\Unit\Waste\Infrastructure\Repository\Waste;

use App\Shared\Domain\Model\Locale\Locale;
use App\Waste\Domain\Model\Waste\WasteRepository;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryTest;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryMother;

class InMemoryWasteRepositoryTest extends WasteRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): WasteRepository
    {
        return WasteRepositoryMother::inMemory($locale);
    }
}