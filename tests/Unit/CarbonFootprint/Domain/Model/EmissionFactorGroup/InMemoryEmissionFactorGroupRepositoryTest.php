<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryEmissionFactorGroupRepositoryTest extends EmissionFactorGroupRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): EmissionFactorGroupRepository
    {
        return EmissionFactorGroupRepositoryMother::inMemory($locale);
    }
}
