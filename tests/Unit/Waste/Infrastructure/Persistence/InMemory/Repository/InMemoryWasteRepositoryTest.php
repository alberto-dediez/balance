<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Infrastructure\Persistence\InMemory\Repository;

use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryTest;
use App\Waste\Domain\Model\Waste\WasteRepository;

class InMemoryWasteRepositoryTest extends WasteRepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): WasteRepository
    {
        return WasteRepositoryMother::inMemory($locale);
    }
}
