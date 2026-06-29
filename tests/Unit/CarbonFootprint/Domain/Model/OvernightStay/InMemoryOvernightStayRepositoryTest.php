<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay;

use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryOvernightStayRepositoryTest extends OvernightStayRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): OvernightStayRepository
    {
        return OvernightStayRepositoryMother::inMemory($locale);
    }
}
