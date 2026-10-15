<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile;

use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryUseProfileRepositoryTest extends UseProfileRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): UseProfileRepository
    {
        return UseProfileRepositoryMother::inMemory($locale);
    }
}
