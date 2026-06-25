<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Material;

use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryMaterialRepositoryTest extends MaterialRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): MaterialRepository
    {
        return MaterialRepositoryMother::inMemory($locale);
    }
}
