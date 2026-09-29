<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialitySector;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryMaterialitySectorRepositoryTest extends MaterialitySectorRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): MaterialitySectorRepository
    {
        return MaterialitySectorRepositoryMother::inMemory($locale);
    }
}
