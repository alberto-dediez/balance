<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService;

use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\Shared\Domain\Model\Locale\Locale;

class InMemoryPurchaseServiceRepositoryTest extends PurchaseServiceRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): PurchaseServiceRepository
    {
        return PurchaseServiceRepositoryMother::inMemory($locale);
    }
}
