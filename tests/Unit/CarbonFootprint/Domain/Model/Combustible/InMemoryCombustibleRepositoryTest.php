<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible;

use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryCombustibleRepositoryTest extends CombustibleRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): CombustibleRepository
    {
        return CombustibleRepositoryMother::inMemory($locale);
    }
}
