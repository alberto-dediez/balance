<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPurchaseServiceRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class PurchaseServiceRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryPurchaseServiceRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryPurchaseServiceRepository();
    }
}
