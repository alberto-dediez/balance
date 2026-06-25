<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem;

use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryMerchandisingItemRepositoryTest extends MerchandisingItemRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): MerchandisingItemRepository
    {
        return MerchandisingItemRepositoryMother::inMemory($locale);
    }
}
