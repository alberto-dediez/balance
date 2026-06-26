<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryCateringItemRepositoryTest extends CateringItemRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): CateringItemRepository
    {
        return CateringItemRepositoryMother::inMemory($locale);
    }
}
