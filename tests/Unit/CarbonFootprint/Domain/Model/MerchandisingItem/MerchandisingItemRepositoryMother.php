<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMerchandisingItemRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class MerchandisingItemRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryMerchandisingItemRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryMerchandisingItemRepository();
    }
}
