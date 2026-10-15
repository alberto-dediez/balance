<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryCapitalGoodRepositoryTest extends CapitalGoodRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): CapitalGoodRepository
    {
        return CapitalGoodRepositoryMother::inMemory($locale);
    }
}
